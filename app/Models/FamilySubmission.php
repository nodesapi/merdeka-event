<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'event_id',
    'reference_code',
    'head_of_family_name',
    'resident_block',
    'phone_number',
    'email',
    'notes',
    'recommended_amount',
    'submitted_total',
    'payment_method',
    'proof_file',
    'payment_notes',
    'status',
    'admin_notes',
    'verified_at',
])]
class FamilySubmission extends Model
{
    use HasFactory, HasUuidV7;

    protected function casts(): array
    {
        return [
            'recommended_amount' => 'decimal:2',
            'submitted_total' => 'decimal:2',
            'verified_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function contributionItems(): HasMany
    {
        return $this->hasMany(ContributionItem::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function getProofFileUrlAttribute(): ?string
    {
        if (! $this->proof_file) {
            return null;
        }

        return '/storage/' . ltrim($this->proof_file, '/');
    }
}
