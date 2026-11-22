<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use BelongsToTenant;

    protected $table = 'attendance';

    protected $fillable = ['tenant_id', 
        'child_id', 'date', 'check_in_at', 'checked_in_by',
        'check_out_at', 'checked_out_by', 'pickup_person_id', 'note',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function pickupPerson(): BelongsTo
    {
        return $this->belongsTo(PickupPerson::class);
    }
}
