<?php

namespace App\Support;

use App\Models\AuditLog;

class Audit
{
    public static function record(string $action, string $entity, ?int $entityId, string $details = ''): void
    {
        $user = auth()->user();
        AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'details' => $details,
        ]);
    }
}
