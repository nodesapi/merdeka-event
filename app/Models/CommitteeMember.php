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
    'position',
    'level',
    'resident_block',
    'phone_number',
    'photo',
    'sort_order',
    'is_active',
])]
class CommitteeMember extends Model
{
    use HasFactory, HasUuidV7;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'level' => 'integer',
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
