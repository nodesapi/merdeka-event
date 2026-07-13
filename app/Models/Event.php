<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Support\Carbon;

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
    'bazaar_poster_path',
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

    public function bazaarSubmissions(): HasMany
    {
        return $this->hasMany(BazaarSubmission::class);
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
     * Human-friendly Indonesian date range label that handles single-day and
     * multi-day ranges (e.g. "17 Agustus 2026" or "16 – 17 Agustus 2026").
     */
    public static function formatDateRange(?Carbon $start, ?Carbon $end): ?string
    {
        if (! $start) {
            return null;
        }

        $start = $start->locale('id');

        if (! $end || $start->isSameDay($end)) {
            return $start->translatedFormat('d F Y');
        }

        $end = $end->locale('id');

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
     * Human-friendly Indonesian schedule label for the event's own start/end date.
     */
    public function getScheduleLabelAttribute(): ?string
    {
        return static::formatDateRange($this->start_date, $this->end_date);
    }

    /**
     * Human-friendly Indonesian label listing distinct dates (not a continuous
     * range) — e.g. "9, 16 & 17 Agustus 2026" for activities that happen on
     * specific separate days rather than every day in between.
     */
    public static function formatDateList(iterable $dates): ?string
    {
        $dates = collect($dates)
            ->filter()
            ->unique(fn (Carbon $date) => $date->format('Y-m-d'))
            ->sort()
            ->values();

        if ($dates->isEmpty()) {
            return null;
        }

        if ($dates->count() === 1) {
            return $dates->first()->locale('id')->translatedFormat('d F Y');
        }

        $sameMonth = $dates->every(
            fn (Carbon $date) => $date->isSameMonth($dates->first()) && $date->year === $dates->first()->year
        );

        if ($sameMonth) {
            $days = $dates->map(fn (Carbon $date) => $date->translatedFormat('d'))->all();
            $last = array_pop($days);

            $joined = $days ? implode(', ', $days) . ' & ' . $last : $last;

            return $joined . ' ' . $dates->first()->locale('id')->translatedFormat('F Y');
        }

        $parts = $dates->map(fn (Carbon $date) => $date->locale('id')->translatedFormat('d F Y'))->all();
        $last = array_pop($parts);

        return $parts ? implode(', ', $parts) . ' & ' . $last : $last;
    }

    /**
     * Whether the event spans more than one calendar day.
     */
    public function getIsMultiDayAttribute(): bool
    {
        return $this->start_date && $this->end_date && ! $this->start_date->isSameDay($this->end_date);
    }

    public function getBazaarPosterUrlAttribute(): ?string
    {
        return $this->bazaar_poster_path ? '/storage/' . ltrim($this->bazaar_poster_path, '/') : null;
    }
}
