<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/** Jejak audit: siapa melakukan apa, atas objek apa, dari alamat mana. */
class Audit
{
    public static function log(string $action, string $description, mixed $subject = null, array $properties = [], ?int $userId = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'subject_type' => $subject instanceof Model ? class_basename($subject) : null,
            'subject_id' => $subject instanceof Model ? $subject->getKey() : null,
            'description' => mb_substr($description, 0, 250),
            'properties' => $properties ?: null,
            'ip' => app()->runningInConsole() ? null : request()->ip(),
            'created_at' => now(),
        ]);
    }
}
