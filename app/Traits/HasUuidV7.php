<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

trait HasUuidV7
{
    use HasUuids;

    /**
     * Generate a new UUID (v7) for the model.
     */
    public function newUniqueId(): string
    {
        return (string) Str::uuid7();
    }
}
