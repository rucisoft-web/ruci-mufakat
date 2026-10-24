<?php

namespace App\Models;

use App\Enums\DataOrigin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Training extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'held_on' => 'date',
            'hours' => 'float',
            'data_origin' => DataOrigin::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
