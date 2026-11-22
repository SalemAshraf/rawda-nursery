<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incident extends Model
{
    use BelongsToTenant;

    public const SEVERITIES = ['minor', 'moderate', 'severe'];

    protected $fillable = ['tenant_id', 'family_visible',
        'child_id', 'occurred_at', 'severity', 'description', 'reported_by',
        'guardian_acknowledged_at', 'guardian_acknowledged_by',
    ];

    protected $casts = ['occurred_at' => 'datetime', 'guardian_acknowledged_at' => 'datetime'];

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
