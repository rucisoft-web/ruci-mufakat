<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiInteraction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'context_refs' => 'array',
            'output' => 'array',
            'is_draft' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
