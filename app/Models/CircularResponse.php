<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CircularResponse extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function circular(): BelongsTo
    {
        return $this->belongsTo(CircularResolution::class, 'circular_resolution_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
