<?php

namespace App\Models;

use App\Enums\VoteChoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'vote' => VoteChoice::class,
        ];
    }

    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
