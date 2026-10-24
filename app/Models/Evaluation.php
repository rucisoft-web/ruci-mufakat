<?php

namespace App\Models;

use App\Enums\DataOrigin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'data_origin' => DataOrigin::class,
        ];
    }

    public function organ(): BelongsTo
    {
        return $this->belongsTo(Organ::class);
    }
}
