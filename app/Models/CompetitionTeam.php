<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'competition_id',
    'team_name',
    'round',
    'status',
    'rank',
    'notes',
])]
class CompetitionTeam extends Model
{
    use HasFactory, HasUuidV7;

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(CompetitionParticipant::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->team_name ?: 'Tim #' . substr($this->id, 0, 8);
    }
}
