<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable([
    'competition_id',
    'category_key',
    'round',
    'heat_number',
    'is_third_place',
    'winner_entrant_id',
])]
class CompetitionMatch extends Model
{
    use HasFactory, HasUuidV7;

    protected function casts(): array
    {
        return [
            'is_third_place' => 'boolean',
        ];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function entrants(): HasMany
    {
        return $this->hasMany(CompetitionMatchEntrant::class);
    }

    public function isDecided(): bool
    {
        return $this->winner_entrant_id !== null;
    }

    /**
     * Heat ini adalah Final kategori tersebut: satu-satunya heat non-Juara-3 di
     * babak terakhir kategori ini (kategori lain punya babak terakhirnya sendiri).
     * Dipakai baik oleh BracketGenerator (nentuin berapa placement wajib diisi)
     * maupun langsung dari Blade (nentuin gaya tampilan medali vs "Lolos" biasa).
     */
    public function isFinalMatch(): bool
    {
        if ($this->is_third_place) {
            return false;
        }

        $maxRound = static::where('competition_id', $this->competition_id)
            ->where('category_key', $this->category_key)
            ->max('round');

        if ($this->round !== $maxRound) {
            return false;
        }

        return static::where('competition_id', $this->competition_id)
            ->where('category_key', $this->category_key)
            ->where('round', $maxRound)
            ->where('is_third_place', false)
            ->count() === 1;
    }

    /**
     * Berapa peringkat yang WAJIB diisi sebelum heat ini dianggap selesai:
     * - Partai Juara 3: cuma perlu 1 (siapa yang menang, gugur Juara 4+ tidak ada).
     * - Final kategori: sampai 3 (Juara 1/2/3), dibatasi jumlah entrant kalau < 3.
     * - Heat biasa: `winners_per_heat` lomba ini (mode "vs" selalu 1), dibatasi
     *   jumlah entrant heat ini kalau lebih kecil dari itu.
     */
    public function requiredPlacements(): int
    {
        $entrantCount = $this->entrants->count();

        if ($this->is_third_place) {
            return min(1, $entrantCount);
        }

        if ($this->isFinalMatch()) {
            return min(3, $entrantCount);
        }

        $winnersPerHeat = max(1, (int) ($this->competition->winners_per_heat ?? 1));

        return min($winnersPerHeat, $entrantCount);
    }

    public function placementFor(string $entrantId): ?int
    {
        return $this->entrants->firstWhere('entrant_id', $entrantId)?->placement;
    }

    public function hasAnyPlacement(): bool
    {
        return $this->entrants->contains(fn (CompetitionMatchEntrant $e) => $e->placement !== null);
    }

    /**
     * Resolve entrant/pemenang heat ini jadi model CompetitionParticipant atau
     * CompetitionTeam dari peta yang sudah di-preload (hindari query per-heat/N+1).
     *
     * @param  Collection<string, CompetitionParticipant|CompetitionTeam>  $entrantsById
     * @return Collection<int, CompetitionParticipant|CompetitionTeam>
     */
    public function resolveEntrants(Collection $entrantsById): Collection
    {
        return $this->entrants
            ->map(fn (CompetitionMatchEntrant $e) => $entrantsById->get($e->entrant_id))
            ->filter()
            ->values();
    }

    public function winnerEntrant(Collection $entrantsById): CompetitionParticipant|CompetitionTeam|null
    {
        return $this->winner_entrant_id ? $entrantsById->get($this->winner_entrant_id) : null;
    }
}
