<?php

namespace App\Models\Concerns;

use App\Models\Scope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToScope
{
    public function scope(): BelongsTo
    {
        return $this->belongsTo(Scope::class);
    }
}
