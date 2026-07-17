<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_id',
    'name',
    'logo',
    'sort_order',
])]
class Sponsor extends Model
{
    use HasFactory, HasUuidV7;

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? '/storage/' . ltrim($this->logo, '/') : null;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
