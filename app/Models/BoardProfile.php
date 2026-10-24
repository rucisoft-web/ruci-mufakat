<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardProfile extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expertise' => 'array',
            'is_independent' => 'boolean',
            'independence_declared_at' => 'date',
            'onboarded_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
