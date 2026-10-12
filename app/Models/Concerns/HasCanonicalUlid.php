<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Support\Str;

trait HasCanonicalUlid
{
    use HasUlids;

    public function newUniqueId(): string
    {
        return strtoupper((string) Str::ulid());
    }
}
