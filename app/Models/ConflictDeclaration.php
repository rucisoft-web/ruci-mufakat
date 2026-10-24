<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConflictDeclaration extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'recused' => 'boolean',
            'counts_for_quorum' => 'boolean',
            'declared_at' => 'datetime',
        ];
    }

    public function agendaItem(): BelongsTo
    {
        return $this->belongsTo(AgendaItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
