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
    public function start(int $linesPerMatch): void
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

        DB::transaction(function () use ($byCategory, $linesPerMatch) {
            $this->competition->update(['bracket_lines_per_match' => $linesPerMatch]);
            $this->entrantQuery()->update(['round' => 1, 'status' => 'active', 'rank' => null]);

            foreach ($byCategory as $categoryKey => $pool) {
                if ($linesPerMatch === 2) {
                    // Mode "vs" dipadatkan ke pangkat 2 terdekat (dengan bye) supaya
                    // bagan bisa dibagi rata kiri-kanan konvergen ke final.
                    $this->createSeededTreeRound($categoryKey, $pool->shuffle());
                } else {
                    $this->createRound($categoryKey, 1, $pool->shuffle());
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

            if ($ids->count() === 1) {
                $this->decideMatch($match, $ids->first());
            }
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

        $winnerIds = $matches->pluck('winner_entrant_id')->filter()->values();

        if ($winnerIds->count() <= 1) {
            throw new InvalidArgumentException('Kategori ini sudah selesai — hanya tersisa satu pemenang.');
        }

        $entrantsById = $this->entrantQuery()->whereIn('id', $winnerIds)->get()->keyBy('id');
        $winners = $winnerIds->map(fn ($id) => $entrantsById->get($id))->filter()->values();

        DB::transaction(function () use ($categoryKey, $currentRound, $matches, $winners) {
            $this->createRound($categoryKey, $currentRound + 1, $winners);

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
            $this->competition->update(['bracket_lines_per_match' => null]);
            $this->entrantQuery()->update(['round' => 1, 'status' => 'active', 'rank' => null]);
        });
    }

    /**
     * Tandai pemenang sebuah heat — entrant lain di heat yang sama otomatis gugur
     * (kecuali heat bye yang cuma berisi 1 orang, tidak ada yang perlu digugurkan).
     * Kalau heat ini adalah Final atau partai Juara 3 kategori tersebut, rank 1/2/3
     * otomatis ditetapkan juga — konsisten dengan tombol Juara di layar Peserta.
     */
    public function decideMatch(CompetitionMatch $match, string $winnerEntrantId): void
    {
        $entrantIds = $match->entrants()->pluck('entrant_id');

        if (! $entrantIds->contains($winnerEntrantId)) {
            throw new InvalidArgumentException('Peserta/tim ini bukan bagian dari heat ini.');
        }

        $match->update(['winner_entrant_id' => $winnerEntrantId]);

        $loserIds = $entrantIds->reject(fn ($id) => $id === $winnerEntrantId);

        if ($loserIds->isNotEmpty()) {
            $this->entrantQuery()->whereIn('id', $loserIds)->update(['status' => 'eliminated']);
        }

        if ($match->is_third_place) {
            $this->entrantQuery()->where('id', $winnerEntrantId)->update(['rank' => 3]);
        } elseif ($this->isFinalMatch($match)) {
            $this->entrantQuery()->where('id', $winnerEntrantId)->update(['rank' => 1]);

            if ($loserIds->isNotEmpty()) {
                $this->entrantQuery()->whereIn('id', $loserIds)->update(['rank' => 2]);
            }
        }
    }

    /**
     * Heat ini adalah Final kategori tersebut: satu-satunya heat non-Juara-3 di
     * babak terakhir KATEGORI ini (kategori lain punya babak terakhirnya sendiri).
     */
    protected function isFinalMatch(CompetitionMatch $match): bool
    {
        if ($match->is_third_place) {
            return false;
        }

        $maxRound = $this->competition->matches()->where('category_key', $match->category_key)->max('round');

        return $match->round === $maxRound
            && $this->competition->matches()
                ->where('category_key', $match->category_key)
                ->where('round', $maxRound)
                ->where('is_third_place', false)
                ->count() === 1;
    }

    /**
     * Batalkan pemenang sebuah heat — hanya boleh kalau babak berikutnya KATEGORI
     * ini belum dibuat.
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

        if ($match->is_third_place || $this->isFinalMatch($match)) {
            $this->entrantQuery()->whereIn('id', $entrantIds)->update(['rank' => null]);
        }

        $match->update(['winner_entrant_id' => null]);
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

            // Heat cuma 1 orang (sisa ganjil) = bye, otomatis menang tanpa tanding.
            if ($ids->count() === 1) {
                $this->decideMatch($match, $ids->first());
            }
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
