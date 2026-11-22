<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TuitionPlan extends Model
{
    use BelongsToTenant;

    public const PERIODS = ['monthly', 'term', 'yearly'];

    protected $fillable = ['tenant_id', 'name', 'amount_minor', 'period', 'active'];

    protected $casts = ['active' => 'boolean', 'amount_minor' => 'integer'];
}
