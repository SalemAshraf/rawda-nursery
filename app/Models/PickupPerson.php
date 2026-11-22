<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupPerson extends Model
{
    use BelongsToTenant;

    protected $table = 'pickup_persons';

    protected $fillable = ['tenant_id', 'child_id', 'name', 'relation', 'phone', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }
}
