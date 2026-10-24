<?php

namespace App\Models;

use App\Enums\Rsvp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingParticipant extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'has_vote' => 'boolean',
            'attended' => 'boolean',
            'rsvp' => Rsvp::class,
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'access_until' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organ(): BelongsTo
    {
        return $this->belongsTo(Organ::class);
    }
}
