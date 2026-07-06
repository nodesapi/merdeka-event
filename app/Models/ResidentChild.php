<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'name', 'age', 'gender'])]
class ResidentChild extends Model
{
    use HasFactory, HasUuidV7;

    /**
     * Get the user (parent/resident) that owns this child.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
