<?php

namespace App\Models;

use App\Enums\MinuteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Minute extends Model
{
    protected $table = 'minutes';

    protected $guarded = [];

    /** Risalah final tidak pernah ditimpa atau dihapus; koreksi selalu lewat addendum. */
    protected static function booted(): void
    {
        static::updating(function (Minute $minute) {
            if ($minute->getOriginal('status') === MinuteStatus::Final && $minute->isDirty(['body', 'status', 'content_hash', 'meeting_id'])) {
                throw new \RuntimeException('Risalah final tidak dapat diubah. Gunakan koreksi/addendum.');
            }
        });
        static::deleting(function (Minute $minute) {
            if ($minute->status === MinuteStatus::Final) {
                throw new \RuntimeException('Risalah final tidak dapat dihapus.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => MinuteStatus::class,
            'circulated_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function corrects(): BelongsTo
    {
        return $this->belongsTo(Minute::class, 'corrects_id');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(Minute::class, 'corrects_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(MinuteSignature::class);
    }

    public function drafter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'drafted_by');
    }
}
