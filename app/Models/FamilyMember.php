<?php

namespace App\Models;

use App\Support\AgeCategory;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'family_submission_id',
    'event_id',
    'registration_number',
    'name',
    'relationship',
    'age',
    'gender',
    'competition_id',
    'notes',
])]
class FamilyMember extends Model
{
    use HasFactory, HasUuidV7;

    public function familySubmission(): BelongsTo
    {
        return $this->belongsTo(FamilySubmission::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function competitionParticipant(): HasOne
    {
        return $this->hasOne(CompetitionParticipant::class);
    }

    public function competitionParticipations(): HasMany
    {
        return $this->hasMany(CompetitionParticipant::class);
    }

    public function getGenderLabelAttribute(): ?string
    {
        return match ($this->gender) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => null,
        };
    }

    public function getAgeCategoryLabelAttribute(): string
    {
        return AgeCategory::labelFor($this->age !== null ? (int) $this->age : null);
    }
}
