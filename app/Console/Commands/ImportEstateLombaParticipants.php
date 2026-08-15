<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\CompetitionParticipant;
use App\Models\CompetitionTeam;
use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Import peserta lomba dari luar warga (Tim Taman & Tim Security) yang datanya
 * dikirim panitia lewat WhatsApp, bukan lewat form pendaftaran warga.
 *
 * Semua baris dibuat dengan family_member_id = NULL (persis seperti fitur
 * "Tambah Peserta manual / tamu non-warga" yang sudah ada di admin panel),
 * supaya peserta ini TIDAK dapat No. Daftar dan TIDAK masuk pool undian
 * doorprize (keduanya hanya berlaku untuk warga yang terhubung ke FamilyMember).
 *
 * Aman dijalankan berulang kali: sebelum insert, command menghitung selisih
 * antara jumlah nama di roster vs jumlah baris yang sudah ada di database,
 * dan hanya membuat selisihnya. Selalu jalankan --dry-run dulu untuk preview.
 */
class ImportEstateLombaParticipants extends Command
{
    protected $signature = 'lomba:import-estate {--dry-run : Preview tanpa menyimpan apa pun ke database} {--event= : Slug Event, wajib diisi kalau ada lebih dari 1 Event} {--list : Cuma tampilkan semua nama lomba yang ada di event ini, lalu keluar}';

    protected $description = 'Import peserta non-warga (Tim Taman & Tim Security) ke lomba yang sudah ada';

    private int $defaultAge = 25;

    /**
     * @return array<string, array<string, array<int, string>>>
     */
    private function roster(): array
    {
        return [
            'Tim Taman' => [
                'Bola Sarung' => ['Nurdin', 'Rehan', 'Madi', 'Andi', 'Dadi', 'Dede'],
                'Estafet Sarung' => ['Edah', 'Iyos', 'Narsih'],
                'Kait Nusantara' => ['Iyos', 'Dede', 'Madi', 'Edah'],
                'Makan Kerupuk' => ['Iyos', 'Madi', 'Andi', 'Narsih', 'Edah'],
                'Pindah Tepung' => ['Nurdin', 'Dadi', 'Rehan', 'Madi', 'Andi', 'Dede', 'Iyos', 'Narsih', 'Edah'],
                'Tarik Tambang' => ['Andi', 'Nurdin', 'Dani', 'Madi', 'Dede', 'Rehan'],
            ],
            'Tim Security' => [
                'Bola Sarung' => ['Rafael', 'Bili Winaldi', 'Irfansyah', 'Sudaefi', 'Rafael', 'Heriyanto', 'M Roby'],
                'Makan Kerupuk' => ['Rafael', 'Mustomi', 'M.Robi', 'Bili', 'Sudafi', 'Heriyanto'],
                'Tarik Tambang' => ['Rafael', 'Bili Winaldi', 'Irfansyah', 'Sudaefi', 'Rafael', 'Heriyanto', 'M Roby', 'Febi Yansah'],
            ],
        ];
    }

    /**
     * Catatan tambahan per (team_label, nama_lomba) — dokumentasi saja, tidak
     * memengaruhi pengelompokan otomatis (peserta manual tidak punya field gender).
     *
     * @return array<string, array<string, string>>
     */
    private function notesFor(): array
    {
        return [
            'Tim Taman' => [
                'Pindah Tepung' => 'Perempuan',
            ],
        ];
    }

    public function handle(): int
    {
        $event = $this->resolveEvent();

        if (! $event) {
            return self::FAILURE;
        }

        if ($this->option('list')) {
            $this->listCompetitions($event);

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        [$plan, $errors] = $this->buildPlan($event);

        if ($errors !== []) {
            $this->error('Ditemukan masalah, tidak ada satu pun yang diproses:');
            foreach ($errors as $message) {
                $this->line('  - ' . $message);
            }

            return self::FAILURE;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . 'Event: ' . $event->name);

        DB::beginTransaction();

        try {
            $totalCreated = 0;

            foreach ($plan as $item) {
                $this->newLine();
                $competition = $item['competition'];
                $isGroup = $competition->isGroup();
                $this->line("{$item['team_label']} -> {$competition->name}" . ($isGroup ? ' (lomba beregu)' : ' (lomba perorangan)'));

                $teamId = null;

                if ($isGroup) {
                    $team = CompetitionTeam::firstOrCreate(
                        ['competition_id' => $competition->id, 'team_name' => $item['team_label']],
                        ['round' => 1, 'status' => 'active']
                    );
                    $teamId = $team->id;
                }

                $totalCreated += $this->syncNames(
                    $competition->id,
                    $teamId,
                    $item['names'],
                    $item['team_label'],
                    $item['note'],
                    $dryRun
                );
            }
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error('Gagal, semua perubahan dibatalkan: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();

        if ($dryRun) {
            DB::rollBack();
            $this->warn("[DRY RUN] Tidak ada perubahan disimpan ke database. Total baris yang AKAN dibuat: {$totalCreated}.");
            $this->line('Jalankan lagi tanpa --dry-run untuk benar-benar menyimpan.');
        } else {
            DB::commit();
            $this->info("Selesai. Total baris peserta baru dibuat: {$totalCreated}.");
        }

        return self::SUCCESS;
    }

    private function listCompetitions(Event $event): void
    {
        $competitions = Competition::where('event_id', $event->id)->orderBy('name')->get(['name', 'slug', 'type']);

        if ($competitions->isEmpty()) {
            $this->warn('Tidak ada lomba di event ini.');

            return;
        }

        $this->info('Daftar lomba di event "' . $event->name . '":');
        foreach ($competitions as $competition) {
            $this->line('  - ' . $competition->name . ' (slug: ' . $competition->slug . ', tipe: ' . $competition->type . ')');
        }
    }

    private function resolveEvent(): ?Event
    {
        $slug = $this->option('event');

        if ($slug) {
            $event = Event::where('slug', $slug)->first();

            if (! $event) {
                $this->error("Event dengan slug \"{$slug}\" tidak ditemukan.");
            }

            return $event;
        }

        $events = Event::all(['id', 'name', 'slug']);

        if ($events->isEmpty()) {
            $this->error('Tidak ada Event di database.');

            return null;
        }

        if ($events->count() === 1) {
            return $events->first();
        }

        $this->error('Ada lebih dari satu Event, tentukan salah satu dengan --event=<slug>:');
        foreach ($events as $event) {
            $this->line("  - {$event->slug} ({$event->name})");
        }

        return null;
    }

    /**
     * @return array{0: array<int, array{team_label:string, competition:Competition, names:array<int,string>, note:?string}>, 1: array<int,string>}
     */
    private function buildPlan(Event $event): array
    {
        $plan = [];
        $errors = [];

        foreach ($this->roster() as $teamLabel => $competitions) {
            foreach ($competitions as $competitionName => $names) {
                $matches = Competition::where('event_id', $event->id)
                    ->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($competitionName) . '%'])
                    ->get();

                // Semua peserta di roster ini umurnya dianggap Dewasa. Kalau nama lomba
                // dipecah per kategori umur (mis. "Bola Sarung (Anak/Remaja/Dewasa)"),
                // otomatis pilih varian Dewasa-nya daripada memaksa user tentukan manual.
                if ($matches->count() > 1) {
                    $dewasaOnly = $matches->filter(
                        fn (Competition $c) => str_contains(mb_strtolower($c->name), 'dewasa')
                    );

                    if ($dewasaOnly->count() === 1) {
                        $matches = $dewasaOnly->values();
                    }
                }

                if ($matches->count() !== 1) {
                    $errors[] = sprintf(
                        '[%s] "%s": %s',
                        $teamLabel,
                        $competitionName,
                        $matches->isEmpty()
                            ? 'lomba tidak ditemukan di event ini'
                            : 'cocok dengan ' . $matches->count() . ' lomba (' . $matches->pluck('name')->implode(', ') . '), harus persis 1'
                    );

                    continue;
                }

                $plan[] = [
                    'team_label' => $teamLabel,
                    'competition' => $matches->first(),
                    'names' => $names,
                    'note' => $this->notesFor()[$teamLabel][$competitionName] ?? null,
                ];
            }
        }

        return [$plan, $errors];
    }

    /**
     * Buat baris CompetitionParticipant yang belum ada, berdasarkan selisih
     * jumlah nama di roster vs jumlah baris yang sudah tercatat — supaya command
     * ini aman dijalankan berkali-kali tanpa membuat data dobel, tapi tetap bisa
     * membuat 2 baris untuk 2 orang berbeda yang kebetulan namanya sama persis.
     *
     * @param array<int, string> $names
     */
    private function syncNames(string $competitionId, ?string $teamId, array $names, string $teamLabel, ?string $note, bool $dryRun): int
    {
        $targetCounts = [];

        foreach ($names as $rawName) {
            $name = $this->titleCase($rawName);
            $targetCounts[$name] = ($targetCounts[$name] ?? 0) + 1;
        }

        $created = 0;
        $prefix = $dryRun ? '  [DRY RUN] ' : '  ';

        foreach ($targetCounts as $name => $targetCount) {
            $existingCount = CompetitionParticipant::where('competition_id', $competitionId)
                ->where('resident_block', $teamLabel)
                ->whereNull('family_member_id')
                ->where('name', $name)
                ->when(
                    $teamId,
                    fn ($query) => $query->where('competition_team_id', $teamId),
                    fn ($query) => $query->whereNull('competition_team_id')
                )
                ->count();

            $toCreate = max(0, $targetCount - $existingCount);

            if ($toCreate === 0) {
                $this->line("{$prefix}- {$name}: sudah ada di database ({$existingCount}), dilewati.");

                continue;
            }

            for ($i = 0; $i < $toCreate; $i++) {
                CompetitionParticipant::create([
                    'competition_id' => $competitionId,
                    'competition_team_id' => $teamId,
                    'name' => $name,
                    'resident_block' => $teamLabel,
                    'age' => $this->defaultAge,
                    'round' => 1,
                    'status' => 'active',
                    'notes' => $note,
                ]);
            }

            $created += $toCreate;
            $suffix = $existingCount > 0 ? " (sudah ada {$existingCount} sebelumnya)" : '';
            $this->line("{$prefix}- {$name}: dibuat {$toCreate} baris baru{$suffix}.");
        }

        return $created;
    }

    private function titleCase(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));

        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }
}
