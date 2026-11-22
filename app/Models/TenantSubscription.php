<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantSubscription extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['period_ends_at' => 'datetime', 'legacy_access' => 'boolean'];
    }

    public function plan()
    {
        return $this->belongsTo(SaasPlan::class, 'saas_plan_id');
    }

    public function writable(): bool
    {
        return $this->status === 'active' && (! $this->period_ends_at || $this->period_ends_at->isFuture());
    }

    public static function forTenant(?int $id): ?self
    {
        if ($id === null) {
            return null;
        }

return static::where('tenant_id', $id)->first();
    }
}
