<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'kategori',
    'nama_item',
    'volume',
    'satuan',
    'harga_satuan',
    'jumlah_rencana',
    'realisasi',
    'pj',
    'status',
    'catatan',
])]
class RabItem extends Model
{
    use HasFactory, HasUuidV7;

    protected function casts(): array
    {
        return [
            'volume' => 'decimal:2',
            'harga_satuan' => 'decimal:2',
            'jumlah_rencana' => 'decimal:2',
            'realisasi' => 'decimal:2',
        ];
    }

    public function getSelisihAttribute(): float
    {
        return (float) $this->jumlah_rencana - (float) $this->realisasi;
    }
}
