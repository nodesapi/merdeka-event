<?php

namespace App\Models;

use App\Support\AgeCategory;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'competition_id',
    'competition_team_id',
    'family_member_id',
    'name',
    'resident_block',
    'phone_number',
    'age',
    'round',
    'status',
    'rank',
    'notes',
])]
class CompetitionParticipant extends Model
{
    use HasFactory, HasUuidV7;

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(CompetitionTeam::class, 'competition_team_id');
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    public function getAgeCategoryKeyAttribute(): ?string
    {
        return AgeCategory::keyFor($this->age !== null ? (int) $this->age : null);
    }

    public function getAgeCategoryLabelAttribute(): string
    {
        return AgeCategory::labelFor($this->age !== null ? (int) $this->age : null);
    }

    /**
     * Gender tidak di-snapshot di tabel ini (beda dari age) — diambil dari data warga
     * kalau peserta mendaftar via No. Daftar. Peserta manual (tamu non-warga) tidak punya ini.
     */
    public function getGenderAttribute(): ?string
    {
        return $this->familyMember?->gender;
    }

    public function getGenderLabelAttribute(): ?string
    {
        return match ($this->gender) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => null,
        };
    }
}
