<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Guardian extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'phone', 'email', 'relation'];

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Child::class)->withPivot('is_primary');
    }
}
