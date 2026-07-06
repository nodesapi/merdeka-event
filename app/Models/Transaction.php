<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'user_id',
    'contribution_item_id',
    'amount',
    'type',
    'bank_name',
    'account_number',
    'resident_block',
    'proof_file',
    'status',
    'description'
])]
class Transaction extends Model
{
    use HasFactory, HasUuidV7;

    /**
     * Get the user (resident/panitia) associated with this transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contributionItem(): BelongsTo
    {
        return $this->belongsTo(ContributionItem::class);
    }
}
