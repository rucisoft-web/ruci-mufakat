<?php

namespace App\Models;

use App\Enums\DataOrigin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialFact extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'needs_clarification' => 'boolean',
            'data_origin' => DataOrigin::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
