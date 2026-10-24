<?php

namespace App\Models;

use App\Enums\AgendaPurpose;
use App\Enums\Classification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgendaItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'purpose' => AgendaPurpose::class,
            'classification' => Classification::class,
            'materials_submitted_at' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function presenter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'presenter_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'agenda_documents');
    }

    public function conflicts(): HasMany
    {
        return $this->hasMany(ConflictDeclaration::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
