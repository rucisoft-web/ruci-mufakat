<?php

namespace App\Models;

use App\Enums\DataOrigin;
use App\Enums\DecisionKind;
use App\Enums\DecisionResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Decision extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'kind' => DecisionKind::class,
            'result' => DecisionResult::class,
            'decided_on' => 'date',
            'effective_on' => 'date',
            'data_origin' => DataOrigin::class,
        ];
    }

    public function organ(): BelongsTo
    {
        return $this->belongsTo(Organ::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function agendaItem(): BelongsTo
    {
        return $this->belongsTo(AgendaItem::class);
    }

    public function circular(): BelongsTo
    {
        return $this->belongsTo(CircularResolution::class, 'circular_resolution_id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(Decision::class, 'supersedes_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
