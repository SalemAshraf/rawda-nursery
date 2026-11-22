<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'capacity'];

    public function children(): HasMany
    {
        return $this->hasMany(Child::class);
    }
}
