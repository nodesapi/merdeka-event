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
    'type',
    'target_participants',
    'min_age',
    'max_age',
    'min_team_members',
    'max_team_size',
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

    public function teams(): HasMany
    {
        return $this->hasMany(CompetitionTeam::class);
    }

    public function isGroup(): bool
    {
        return $this->type === 'group';
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
     * Apakah jumlah anggota tertentu memenuhi ukuran tim yang disyaratkan.
     */
    public function isTeamSizeEligible(int $memberCount): bool
    {
        if ($this->min_team_members !== null && $memberCount < $this->min_team_members) {
            return false;
        }

        if ($this->max_team_size !== null && $memberCount > $this->max_team_size) {
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

    /**
     * Label ukuran tim untuk ditampilkan (mis. "3–7 anggota per tim").
     */
    public function getTeamSizeLabelAttribute(): ?string
    {
        if ($this->min_team_members === null && $this->max_team_size === null) {
            return null;
        }

        if ($this->min_team_members !== null && $this->max_team_size !== null) {
            return "{$this->min_team_members}–{$this->max_team_size} anggota per tim";
        }

        if ($this->min_team_members !== null) {
            return "Minimal {$this->min_team_members} anggota per tim";
        }

        return "Maksimal {$this->max_team_size} anggota per tim";
    }
}
