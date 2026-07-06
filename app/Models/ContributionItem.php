<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'family_submission_id',
    'type',
    'amount',
    'label',
    'note',
])]
class ContributionItem extends Model
{
    use HasFactory, HasUuidV7;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function familySubmission(): BelongsTo
    {
        return $this->belongsTo(FamilySubmission::class);
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }
}
