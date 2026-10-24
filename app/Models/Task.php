<?php

namespace App\Models;

use App\Enums\DataOrigin;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'due_date' => 'date',
            'original_due_date' => 'date',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'data_origin' => DataOrigin::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class);
    }

    public function agendaItem(): BelongsTo
    {
        return $this->belongsTo(AgendaItem::class);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(TaskMilestone::class);
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(TaskEvidence::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TaskEvent::class);
    }
}
