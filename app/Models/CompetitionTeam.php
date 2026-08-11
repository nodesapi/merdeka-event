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
    'gender_category',
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

    public function getGenderCategoryLabelAttribute(): string
    {
        return match ($this->gender_category) {
            'L' => 'Putra',
            'P' => 'Putri',
            default => 'Tanpa kategori',
        };
    }

    public function getBracketMetaAttribute(): ?string
    {
        $count = $this->relationLoaded('members') ? $this->members->count() : $this->members()->count();

        return $count . ' anggota';
    }

    /**
     * Samakan dengan CompetitionParticipant::display_category_key supaya bagan
     * turnamen bisa mengelompokkan entrant per kategori tanpa peduli jenis lomba —
     * tim tidak punya kategori umur, jadi kategorinya = Putra/Putri.
     */
    public function getDisplayCategoryKeyAttribute(): string
    {
        return $this->gender_category ?? 'none';
    }

    public function getDisplayCategoryLabelAttribute(): string
    {
        return $this->gender_category_label;
    }
}
