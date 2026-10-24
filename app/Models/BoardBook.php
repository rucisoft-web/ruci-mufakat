<?php

namespace App\Models;

use App\Enums\BoardBookStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardBook extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => BoardBookStatus::class,
            'manifest' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(BoardBookRead::class);
    }
}
