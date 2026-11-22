<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLog extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 
        'child_id', 'date', 'meals', 'naps', 'mood', 'activities', 'notes', 'created_by',
    ];

    protected $casts = ['date' => 'date'];

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }
}
