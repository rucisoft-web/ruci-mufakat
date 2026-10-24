<?php

namespace App\Models;

use App\Enums\DataOrigin;
use App\Enums\MeetingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => MeetingStatus::class,
            'scheduled_at' => 'datetime',
            'ends_at' => 'datetime',
            'materials_due_at' => 'datetime',
            'materials_sent_at' => 'datetime',
            'is_closed_session' => 'boolean',
            'started_at' => 'datetime',
            'closed_at' => 'datetime',
            'data_origin' => DataOrigin::class,
        ];
    }

    public function organ(): BelongsTo
    {
        return $this->belongsTo(Organ::class);
    }

    public function chair(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chair_id');
    }

    public function secretary(): BelongsTo
    {
        return $this->belongsTo(User::class, 'secretary_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class);
    }

    public function agendaItems(): HasMany
    {
        return $this->hasMany(AgendaItem::class);
    }

    public function boardBooks(): HasMany
    {
        return $this->hasMany(BoardBook::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class);
    }

    public function minutes(): HasMany
    {
        return $this->hasMany(Minute::class);
    }

    public function informationRequests(): HasMany
    {
        return $this->hasMany(InformationRequest::class);
    }
}
