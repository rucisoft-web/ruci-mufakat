<?php

namespace App\Models;

use App\Enums\Classification;
use App\Enums\DataOrigin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'classification' => Classification::class,
            'data_origin' => DataOrigin::class,
            'ai_allowed' => 'boolean',
            'legal_hold' => 'boolean',
            'retention_until' => 'date',
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

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'previous_version_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(DocumentPage::class);
    }

    public function grants(): HasMany
    {
        return $this->hasMany(DocumentGrant::class);
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(Annotation::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function agendaItems(): BelongsToMany
    {
        return $this->belongsToMany(AgendaItem::class, 'agenda_documents');
    }
}
