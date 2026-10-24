<?php

namespace App\Models;

use App\Enums\DataOrigin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricalOfficial extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'as_of' => 'date',
            'data_origin' => DataOrigin::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
