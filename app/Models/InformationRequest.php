<?php

namespace App\Models;

use App\Enums\DataOrigin;
use App\Enums\InfoRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InformationRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => InfoRequestStatus::class,
            'sla_due_at' => 'datetime',
            'answered_at' => 'datetime',
            'accepted_at' => 'datetime',
            'data_origin' => DataOrigin::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(InformationRequestMessage::class);
    }
}
