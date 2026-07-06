<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'path',
    'sort_order',
])]
class MusicTrack extends Model
{
    use HasUuidV7;

    public function getUrlAttribute(): string
    {
        return '/storage/' . ltrim($this->path, '/');
    }
}
