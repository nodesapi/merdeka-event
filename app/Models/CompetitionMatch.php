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
