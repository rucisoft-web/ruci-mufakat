<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membership extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'has_vote' => 'boolean',
            'is_independent' => 'boolean',
            'is_active' => 'boolean',
            'term_start' => 'date',
            'term_end' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organ(): BelongsTo
    {
        return $this->belongsTo(Organ::class);
    }
}
