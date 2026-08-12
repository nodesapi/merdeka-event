<?php

namespace App\Support;

use App\Models\Competition;
use App\Models\CompetitionMatch;
use App\Models\CompetitionMatchEntrant;
use App\Models\CompetitionParticipant;
use App\Models\CompetitionTeam;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Bagan turnamen berbasis "heat per babak" — babak berikutnya dibentuk ULANG dari
 * kumpulan pemenang heat babak sekarang (bukan pohon match tetap dengan slot
 * berikutnya). Untuk bracket_lines_per_match = 2 ini otomatis menghasilkan pohon
 * head-to-head klasik; untuk > 2 jadi papan heat multi-line (mis. lomba kelereng).
 *
 * Setiap KATEGORI (kategori umur untuk lomba individu, Putra/Putri untuk lomba
 * grup) dapat bagan sendiri-sendiri — sama seperti pengelompokan di layar
 * Peserta & Juara — supaya peserta beda kategori tidak pernah dipertemukan.
 * Kategori yang berbeda maju di babaknya masing-masing secara independen.
 *
 * Tiap heat punya `placement` per entrant (1, 2, 3, ... — kolom di pivot
 * competition_match_entrants), diisi lewat klik BERURUTAN sesuai urutan finish
 * ("klik pertama = peringkat 1", dst). Heat dianggap selesai begitu jumlah
 * peringkat terisi mencapai `CompetitionMatch::requiredPlacements()` — 1 untuk
 * partai Juara 3, sampai 3 untuk Final (Juara 1/2/3), atau `winners_per_heat`
 * lomba ini untuk heat biasa (selalu 1 di mode "vs"). `winner_entrant_id` tetap
 * disinkronkan ke entrant peringkat-1 supaya kode lama (isDecided/winnerEntrant)
 * tidak perlu berubah.
 */
class BracketGenerator
{
    public function __construct(protected Competition $competition)
    {
    }

    /**
     * Mulai bagan babak 1 untuk SEMUA kategori entrant lomba ini sekaligus (reset
     * round/status/rank mereka ke awal). Kategori dengan entrant < 2 dilewati
     * (tidak cukup untuk bertanding). Tolak kalau bagan sudah ada.
     */
    public function start(int $linesPerMatch, int $winnersPerHeat = 1): void
    {
        if ($linesPerMatch < 2) {
            throw new InvalidArgumentException('Jumlah peserta/tim per pertandingan minimal 2.');
        }

        if ($this->competition->hasBracket()) {
            throw new InvalidArgumentException('Bagan sudah ada. Hapus bagan dulu kalau mau membuat ulang.');
        }

        $byCategory = $this->competition->entrantsByCategory()->filter(fn ($group) => $group->count() >= 2);

        if ($byCategory->isEmpty()) {
            throw new InvalidArgumentException('Minimal 2 peserta/tim dalam satu kategori untuk membuat bagan.');
        }

        // Mode "vs" (1 lawan 1) selalu tepat 1 pemenang per heat — pengaturan
        // "jumlah pemenang per heat" cuma relevan untuk papan heat multi-line.
        $effectiveWinnersPerHeat = $linesPerMatch === 2 ? 1 : max(1, $winnersPerHeat);

        DB::transaction(function () use ($byCategory, $linesPerMatch, $effectiveWinnersPerHeat) {
            $this->competition->update([
                'bracket_lines_per_match' => $linesPerMatch,
                'winners_per_heat' => $effectiveWinnersPerHeat,
            ]);
            $this->entrantQuery()->update(['round' => 1, 'status' => 'active', 'rank' => null]);

            foreach ($byCategory as $categoryKey => $pool) {
                if ($linesPerMatch === 2) {
                    // Mode "vs" dipadatkan ke pangkat 2 terdekat (dengan bye) supaya
                    // bagan bisa dibagi rata kiri-kanan konvergen ke final.
                    $this->createSeededTreeRound($categoryKey, $this->seedPool($pool));
                } else {
                    $this->createRound($categoryKey, 1, $this->seedPool($pool));
                }
            }
        });
    }

    /**
     * Seeding babak 1 khusus mode "vs" (N=2): padatkan jumlah entrant ke pangkat 2
     * terdekat — sejumlah heat pertama dapat 1 entrant saja (bye, otomatis menang),
     * sisanya dapat pasangan penuh. Jumlah heat bye selalu < total heat, jadi tidak
     * pernah ada heat kosong.
     *
     * @param  Collection<int, CompetitionParticipant|CompetitionTeam>  $pool
     */
    protected function createSeededTreeRound(string $categoryKey, Collection $pool): void
    {
        $entrants = $pool->values();
        $n = $entrants->count();

        $size = 1;
        while ($size < $n) {
            $size *= 2;
        }

        $heatCount = intdiv($size, 2);
        $byeCount = $size - $n;

        $cursor = 0;

        for ($i = 0; $i < $heatCount; $i++) {
            $heatSize = $i < $byeCount ? 1 : 2;
            $entrantsInHeat = $entrants->slice($cursor, $heatSize);
            $cursor += $heatSize;

            $match = CompetitionMatch::create([
                'competition_id' => $this->competition->id,
                'category_key' => $categoryKey,
                'round' => 1,
                'heat_number' => $i + 1,
            ]);

            $ids = $entrantsInHeat->pluck('id');

            foreach ($ids as $entrantId) {
                CompetitionMatchEntrant::create([
                    'competition_match_id' => $match->id,
                    'entrant_id' => $entrantId,
                ]);
            }

            $this->entrantQuery()->whereIn('id', $ids)->update(['round' => 1]);

            $this->autoFinalizeIfTrivial($match, $ids);
        }
    }

    /**
     * Bentuk babak berikutnya untuk SATU kategori, dari kumpulan pemenang heat
     * kategori itu di babak saat ini. Kategori lain tidak terpengaruh — masing-
     * masing kategori maju di babaknya sendiri, dengan kecepatan sendiri.
     */
    public function nextRound(string $categoryKey): void
    {
        $currentRound = (int) $this->competition->matches()->where('category_key', $categoryKey)->max('round');

        if ($currentRound === 0) {
            throw new InvalidArgumentException('Bagan kategori ini belum dibuat.');
        }

        $matches = $this->competition->matches()
            ->where('category_key', $categoryKey)
            ->where('round', $currentRound)
            ->orderBy('heat_number')
            ->get();

        if ($matches->contains(fn (CompetitionMatch $m) => ! $m->isDecided())) {
            throw new InvalidArgumentException('Masih ada heat di babak ini yang belum ada pemenangnya.');
        }

        // Pemenang = entrant yang punya peringkat (bisa lebih dari 1 per heat kalau
        // winners_per_heat > 1), bukan cuma winner_entrant_id (peringkat 1 saja).
        $winnerIds = CompetitionMatchEntrant::whereIn('competition_match_id', $matches->pluck('id'))
            ->whereNotNull('placement')
            ->pluck('entrant_id');

        if ($winnerIds->count() <= 1) {
            throw new InvalidArgumentException('Kategori ini sudah selesai — hanya tersisa satu pemenang.');
        }

        $entrantsById = $this->entrantQuery()->whereIn('id', $winnerIds)->get()->keyBy('id');
        $winners = $winnerIds->map(fn ($id) => $entrantsById->get($id))->filter()->values();

        DB::transaction(function () use ($categoryKey, $currentRound, $matches, $winners) {
            $this->createRound($categoryKey, $currentRound + 1, $this->seedPool($winners));

            // Transisi semifinal -> final di mode "vs": kalau babak sekarang persis
            // 2 heat dan keduanya pertandingan asli (bukan bye), buat juga partai
            // perebutan Juara 3 antara kedua yang kalah semifinal.
            if ((int) $this->competition->bracket_lines_per_match === 2 && $matches->count() === 2) {
                $this->maybeCreateThirdPlaceMatch($categoryKey, $currentRound + 1, $matches);
            }
        });
    }

    /**
     * Buat heat perebutan Juara 3 dari dua kalah semifinal — dilewati kalau salah
     * satu semifinal cuma bye (tidak ada lawan asli, jadi tidak ada "yang kalah").
     */
    protected function maybeCreateThirdPlaceMatch(string $categoryKey, int $finalRound, Collection $semifinalMatches): void
    {
        $loserIds = collect();

        foreach ($semifinalMatches as $match) {
            $entrantIds = $match->entrants()->pluck('entrant_id');

            if ($entrantIds->count() < 2) {
                return; // salah satu semifinal bye — tidak ada pasangan Juara 3 yang adil.
            }

            $loserIds->push($entrantIds->reject(fn ($id) => $id === $match->winner_entrant_id)->first());
        }

        $thirdPlaceMatch = CompetitionMatch::create([
            'competition_id' => $this->competition->id,
            'category_key' => $categoryKey,
            'round' => $finalRound,
            'heat_number' => 2,
            'is_third_place' => true,
        ]);

        foreach ($loserIds as $entrantId) {
            CompetitionMatchEntrant::create([
                'competition_match_id' => $thirdPlaceMatch->id,
                'entrant_id' => $entrantId,
            ]);
        }
    }

    /**
     * Hapus seluruh bagan (semua kategori, semua heat & anggotanya) dan kembalikan
     * entrant ke awal.
     */
    public function reset(): void
    {
        DB::transaction(function () {
            $this->competition->matches()->delete();
            $this->competition->update(['bracket_lines_per_match' => null, 'winners_per_heat' => null]);
            $this->entrantQuery()->update(['round' => 1, 'status' => 'active', 'rank' => null]);
        });
    }

    /**
     * Catat peringkat berikutnya seorang entrant di sebuah heat — dipanggil sekali
     * per klik, sesuai urutan finish ("klik pertama" jadi peringkat 1, dst). Kalau
     * ini melengkapi jumlah peringkat wajib heat ini (lihat
     * `CompetitionMatch::requiredPlacements()`), heat otomatis "selesai": entrant
     * yang tidak kebagian peringkat digugurkan, dan kalau ini Final/Juara-3, rank
     * global 1/2/3 disinkronkan juga (dipakai layar Peserta & Juara / hadiah).
     */
    public function recordPlacement(CompetitionMatch $match, string $entrantId): void
    {
        $entrantIds = $match->entrants()->pluck('entrant_id');

        if (! $entrantIds->contains($entrantId)) {
            throw new InvalidArgumentException('Peserta/tim ini bukan bagian dari heat ini.');
        }

        $alreadyPlaced = $match->entrants()->where('entrant_id', $entrantId)->value('placement');

        if ($alreadyPlaced !== null) {
            throw new InvalidArgumentException('Peserta/tim ini sudah punya peringkat di heat ini.');
        }

        $placedCount = $match->entrants()->whereNotNull('placement')->count();
        $match->entrants()->where('entrant_id', $entrantId)->update(['placement' => $placedCount + 1]);
        $match->refresh();

        $this->maybeAutoFillLastRemaining($match, $entrantIds);
        $this->finalizeHeatIfReady($match, $entrantIds);
    }

    /**
     * Tutup heat biasa (BUKAN Final/Juara-3) lebih awal, walau belum mencapai
     * `winners_per_heat` — dipakai kalau panitia sengaja cuma mau loloskan
     * sebagian dari heat tertentu meski setting globalnya lebih besar (mis. lomba
     * di-set 3 pemenang per heat, tapi di satu heat cuma 1 yang layak lolos).
     * Heat lain di babak yang sama TIDAK ikut terpengaruh, tetap bisa diisi
     * sampai penuh sesuai setting. Final/Juara-3 tidak boleh ditutup sebagian —
     * urutannya harus lengkap supaya Juara 1/2/3 jelas buat pembagian hadiah.
     */
    public function finalizeHeatEarly(CompetitionMatch $match): void
    {
        if ($match->is_third_place || $match->isFinalMatch()) {
            throw new InvalidArgumentException('Final dan partai Juara 3 harus diisi lengkap dulu, tidak bisa diselesaikan sebagian.');
        }

        $entrantIds = $match->entrants()->pluck('entrant_id');
        $placedCount = $match->entrants()->whereNotNull('placement')->count();

        if ($placedCount < 1) {
            throw new InvalidArgumentException('Pilih minimal 1 pemenang dulu sebelum menyelesaikan heat ini.');
        }

        $this->finalizeHeatIfReady($match, $entrantIds, force: true);
    }

    /**
     * Kalau tepat 1 slot peringkat & 1 entrant yang tersisa (mis. setelah memilih
     * Juara 1 dari duel 2 orang, sisanya cuma 1 kandidat buat Juara 2) — isi
     * otomatis, tidak perlu klik lagi karena tidak ada pilihan lain yang mungkin.
     * SENGAJA cuma jalan kalau kandidatnya persis 1 — kalau masih >1 kandidat buat
     * >1 slot tersisa, urutannya di antara mereka tetap ambigu (penting buat heat
     * yang berperingkat/Final, jangan ditebak).
     */
    protected function maybeAutoFillLastRemaining(CompetitionMatch $match, Collection $entrantIds): void
    {
        $required = $match->requiredPlacements();
        $placedCount = $match->entrants()->whereNotNull('placement')->count();
        $remainingSlots = $required - $placedCount;

        if ($remainingSlots !== 1) {
            return;
        }

        $unplacedIds = $match->entrants()->whereNull('placement')->pluck('entrant_id');

        if ($unplacedIds->count() === 1) {
            $match->entrants()->where('entrant_id', $unplacedIds->first())->update(['placement' => $placedCount + 1]);
            $match->refresh();
        }
    }

    /**
     * Heat kecil yang otomatis "tanpa pilihan" — bye (1 entrant), atau heat biasa
     * (bukan Final/Juara-3) yang jumlah entrant-nya sudah <= jumlah yang wajib
     * lolos, jadi semuanya lolos tanpa perlu diklik satu-satu. TIDAK berlaku untuk
     * heat berperingkat (Final/Juara-3) kecuali bye — urutan finish selalu perlu
     * diklik manual di situ, karena posisi Juara 1/2/3 penting.
     */
    protected function autoFinalizeIfTrivial(CompetitionMatch $match, Collection $entrantIds): void
    {
        if ($entrantIds->count() === 1) {
            $match->entrants()->where('entrant_id', $entrantIds->first())->update(['placement' => 1]);
            $match->refresh();
            $this->finalizeHeatIfReady($match, $entrantIds);
            return;
        }

        $isRanked = $match->is_third_place || $match->isFinalMatch();
        $required = $match->requiredPlacements();

        if (! $isRanked && $entrantIds->count() <= $required) {
            foreach ($entrantIds->values() as $index => $entrantId) {
                $match->entrants()->where('entrant_id', $entrantId)->update(['placement' => $index + 1]);
            }
            $match->refresh();
            $this->finalizeHeatIfReady($match, $entrantIds);
        }
    }

    /**
     * Kalau jumlah peringkat yang terisi sudah mencapai yang diwajibkan, tutup
     * heat: sinkronkan winner_entrant_id (kompatibilitas kode lama), gugurkan
     * entrant yang tidak kebagian peringkat, dan sinkronkan rank global untuk
     * Final/Juara-3.
     */
    protected function finalizeHeatIfReady(CompetitionMatch $match, Collection $entrantIds, bool $force = false): void
    {
        $required = $match->requiredPlacements();
        $placedEntries = $match->entrants()->whereNotNull('placement')->orderBy('placement')->get();

        if (! $force && $placedEntries->count() < $required) {
            return; // belum cukup, masih menunggu klik lagi.
        }

        $winnerId = $placedEntries->firstWhere('placement', 1)?->entrant_id;
        $match->update(['winner_entrant_id' => $winnerId]);

        $advancingIds = $placedEntries->pluck('entrant_id');
        $eliminatedIds = $entrantIds->diff($advancingIds);

        if ($eliminatedIds->isNotEmpty()) {
            $this->entrantQuery()->whereIn('id', $eliminatedIds)->update(['status' => 'eliminated']);
        }

        if ($match->is_third_place) {
            if ($winnerId) {
                $this->entrantQuery()->where('id', $winnerId)->update(['rank' => 3]);
            }
        } elseif ($match->isFinalMatch()) {
            foreach ($placedEntries as $entry) {
                $this->entrantQuery()->where('id', $entry->entrant_id)->update(['rank' => $entry->placement]);
            }
        }
    }

    /**
     * Batalkan seluruh peringkat sebuah heat (bukan per-klik — reset heat ini dari
     * awal) — hanya boleh kalau babak berikutnya KATEGORI ini belum dibuat.
     */
    public function undoMatch(CompetitionMatch $match): void
    {
        $nextRoundExists = $this->competition->matches()
            ->where('category_key', $match->category_key)
            ->where('round', $match->round + 1)
            ->exists();

        if ($nextRoundExists) {
            throw new InvalidArgumentException('Tidak bisa dibatalkan — babak berikutnya sudah dibuat.');
        }

        $entrantIds = $match->entrants()->pluck('entrant_id');
        $this->entrantQuery()->whereIn('id', $entrantIds)->update(['status' => 'active']);

        if ($match->is_third_place || $match->isFinalMatch()) {
            $this->entrantQuery()->whereIn('id', $entrantIds)->update(['rank' => null]);
        }

        $match->entrants()->update(['placement' => null]);
        $match->update(['winner_entrant_id' => null]);
    }

    /**
     * Urutkan entrant supaya umur yang berdekatan cenderung masuk heat yang sama
     * (permintaan panitia — kategori umur seperti "Dewasa 16+" tidak punya batas
     * atas, jadi rentang umur di satu kategori bisa lebar). Diacak dulu sebagai
     * tie-breaker acak untuk umur yang sama, baru diurutkan berdasar umur — sisa
     * yang tidak bisa dikelompokkan rata otomatis jatuh ke heat/pasangan terakhir
     * lewat groupIntoHeats()/createSeededTreeRound(). Lomba tim tidak punya kolom
     * umur, jadi tetap acak biasa.
     *
     * @param  Collection<int, CompetitionParticipant|CompetitionTeam>  $pool
     */
    protected function seedPool(Collection $pool): Collection
    {
        if ($this->competition->isGroup()) {
            return $pool->shuffle();
        }

        return $pool->shuffle()->sortBy(fn ($p) => $p->age ?? PHP_INT_MAX)->values();
    }

    protected function entrantQuery(): Builder
    {
        return $this->competition->isGroup()
            ? CompetitionTeam::where('competition_id', $this->competition->id)
            : CompetitionParticipant::where('competition_id', $this->competition->id);
    }

    /**
     * @param  Collection<int, CompetitionParticipant|CompetitionTeam>  $pool
     */
    protected function createRound(string $categoryKey, int $round, Collection $pool): void
    {
        $heats = $this->groupIntoHeats($pool, $this->competition->bracket_lines_per_match);

        foreach ($heats as $index => $entrantsInHeat) {
            $match = CompetitionMatch::create([
                'competition_id' => $this->competition->id,
                'category_key' => $categoryKey,
                'round' => $round,
                'heat_number' => $index + 1,
            ]);

            $ids = collect($entrantsInHeat)->pluck('id');

            foreach ($ids as $entrantId) {
                CompetitionMatchEntrant::create([
                    'competition_match_id' => $match->id,
                    'entrant_id' => $entrantId,
                ]);
            }

            // Label "Babak N" tetap konsisten dengan layar Peserta & Juara yang sudah ada.
            $this->entrantQuery()->whereIn('id', $ids)->update(['round' => $round]);

            $this->autoFinalizeIfTrivial($match, $ids);
        }
    }

    /**
     * Bagi pool jadi heat sedekat mungkin ke $linesPerMatch (rata, bukan "penuh dulu
     * baru sisa di akhir") supaya tidak ada heat kosong-mepet kecuali sisa terakhir
     * memang cuma tinggal 1 (bye).
     *
     * @param  Collection<int, CompetitionParticipant|CompetitionTeam>  $pool
     * @return array<int, array<int, CompetitionParticipant|CompetitionTeam>>
     */
    protected function groupIntoHeats(Collection $pool, int $linesPerMatch): array
    {
        $items = $pool->values()->all();
        $n = count($items);

        if ($n === 0) {
            return [];
        }

        if ($n === 1) {
            return [$items];
        }

        $heatsCount = (int) ceil($n / $linesPerMatch);
        $base = intdiv($n, $heatsCount);
        $remainder = $n % $heatsCount;

        $heats = [];
        $cursor = 0;
        for ($i = 0; $i < $heatsCount; $i++) {
            $size = $base + ($i < $remainder ? 1 : 0);
            $heats[] = array_slice($items, $cursor, $size);
            $cursor += $size;
        }

        return $heats;
    }
}
