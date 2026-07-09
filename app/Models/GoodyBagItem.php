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
    'description',
    'photo',
    'sort_order',
])]
class GoodyBagItem extends Model
{
    use HasFactory, HasUuidV7;

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? '/storage/' . ltrim($this->photo, '/') : null;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
