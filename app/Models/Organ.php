<?php

namespace App\Models;

use App\Enums\OrganKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organ extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'kind' => OrganKind::class,
            'quorum_strictly_more' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Organ::class, 'parent_organ_id');
    }

    public function committees(): HasMany
    {
        return $this->hasMany(Organ::class, 'parent_organ_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    public function workPlanItems(): HasMany
    {
        return $this->hasMany(WorkPlanItem::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }
}
