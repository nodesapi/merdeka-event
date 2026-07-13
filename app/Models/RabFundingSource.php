<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'kategori',
    'sumber',
    'target',
    'realisasi',
    'catatan',
])]
class RabFundingSource extends Model
{
    use HasFactory, HasUuidV7;

    protected function casts(): array
    {
        return [
            'target' => 'decimal:2',
            'realisasi' => 'decimal:2',
        ];
    }

    public function getSelisihAttribute(): float
    {
        return (float) $this->target - (float) $this->realisasi;
    }
}
