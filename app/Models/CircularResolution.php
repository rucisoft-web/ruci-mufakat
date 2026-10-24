<?php

namespace App\Models;

use App\Enums\CircularStatus;
use App\Enums\DataOrigin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CircularResolution extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => CircularStatus::class,
            'circulated_at' => 'datetime',
            'deadline_at' => 'datetime',
            'closed_at' => 'datetime',
            'data_origin' => DataOrigin::class,
        ];
    }

    public function organ(): BelongsTo
    {
        return $this->belongsTo(Organ::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(CircularResponse::class);
    }

    public function decision(): HasOne
    {
        return $this->hasOne(Decision::class);
    }
}
