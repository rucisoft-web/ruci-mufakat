<?php

namespace App\Models;

use App\Enums\DataOrigin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_demo' => 'boolean',
            'ownership_pct' => 'decimal:2',
            'data_origin' => DataOrigin::class,
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_id');
    }

    public function organs(): HasMany
    {
        return $this->hasMany(Organ::class);
    }

    public function historicalOfficials(): HasMany
    {
        return $this->hasMany(HistoricalOfficial::class);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }

    public function financialFacts(): HasMany
    {
        return $this->hasMany(FinancialFact::class);
    }

    public function risks(): HasMany
    {
        return $this->hasMany(Risk::class);
    }
}
