<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'event_id',
    'name',
    'slug',
    'target_participants',
    'min_age',
    'max_age',
    'total_rounds',
    'description',
    'status',
])]
class Competition extends Model
{
    use HasFactory, HasUuidV7;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(CompetitionParticipant::class);
    }

    /**
     * Apakah umur tertentu boleh mengikuti lomba ini.
     */
    public function isAgeEligible(?int $age): bool
    {
        if ($age === null) {
            // Tanpa data umur: hanya boleh bila lomba tak punya batas umur.
            return $this->min_age === null && $this->max_age === null;
        }

        if ($this->min_age !== null && $age < $this->min_age) {
            return false;
        }

        if ($this->max_age !== null && $age > $this->max_age) {
            return false;
        }

        return true;
    }

    /**
     * Label batas umur untuk ditampilkan (mis. "Khusus 1-6 tahun").
     */
    public function getAgeLimitLabelAttribute(): ?string
    {
        if ($this->min_age === null && $this->max_age === null) {
            return null;
        }

        if ($this->min_age !== null && $this->max_age !== null) {
            return "Khusus umur {$this->min_age}–{$this->max_age} tahun";
        }

        if ($this->min_age !== null) {
            return "Minimal {$this->min_age} tahun";
        }

        return "Maksimal {$this->max_age} tahun";
    }
}
