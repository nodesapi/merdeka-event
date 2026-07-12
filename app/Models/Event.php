<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'name',
    'slug',
    'logo',
    'banner',
    'location',
    'maps_url',
    'start_date',
    'end_date',
    'registration_closes_at',
    'status',
    'recommended_contribution_amount',
    'contribution_guidance',
    'description'
])]
class Event extends Model
{
    use HasFactory, HasUuidV7;

    public function competitions(): HasMany
    {
        return $this->hasMany(Competition::class);
    }

    public function committeeMembers(): HasMany
    {
        return $this->hasMany(CommitteeMember::class);
    }

    public function familySubmissions(): HasMany
    {
        return $this->hasMany(FamilySubmission::class);
    }

    public function eventSchedules(): HasMany
    {
        return $this->hasMany(EventSchedule::class);
    }

    public function goodyBagItems(): HasMany
    {
        return $this->hasMany(GoodyBagItem::class);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'registration_closes_at' => 'datetime',
            'recommended_contribution_amount' => 'decimal:2',
        ];
    }

    /**
     * Human-friendly Indonesian schedule label that handles single-day and
     * multi-day events (e.g. "17 Agustus 2026" or "16 – 17 Agustus 2026").
     */
    public function getScheduleLabelAttribute(): ?string
    {
        $start = $this->start_date?->locale('id');
        if (! $start) {
            return null;
        }

        $end = $this->end_date?->locale('id');

        if (! $end || $start->isSameDay($end)) {
            return $start->translatedFormat('d F Y');
        }

        // Same month & year → "16 – 17 Agustus 2026"
        if ($start->isSameMonth($end)) {
            return $start->translatedFormat('d') . ' – ' . $end->translatedFormat('d F Y');
        }

        // Same year, different month → "31 Juli – 1 Agustus 2026"
        if ($start->year === $end->year) {
            return $start->translatedFormat('d F') . ' – ' . $end->translatedFormat('d F Y');
        }

        // Different year → full range
        return $start->translatedFormat('d F Y') . ' – ' . $end->translatedFormat('d F Y');
    }

    /**
     * Whether the event spans more than one calendar day.
     */
    public function getIsMultiDayAttribute(): bool
    {
        return $this->start_date && $this->end_date && ! $this->start_date->isSameDay($this->end_date);
    }
}
