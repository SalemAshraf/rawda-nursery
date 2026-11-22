<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null; // append-only, created_at only

    protected $fillable = ['tenant_id', 'user_id', 'action', 'entity', 'entity_id', 'details'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
