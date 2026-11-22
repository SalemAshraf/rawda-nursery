<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Child extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 
        'classroom_id', 'first_name', 'last_name', 'dob', 'gender',
        'allergies', 'health_notes', 'status', 'enrolled_at',
    ];

    protected $casts = [
        'allergies' => 'string',
        'health_notes' => 'string',
        'dob' => 'date',
        'enrolled_at' => 'date',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class)->withPivot('is_primary');
    }

    public function pickupPersons(): HasMany
    {
        return $this->hasMany(PickupPerson::class);
    }

    public function activePickupPersons(): HasMany
    {
        return $this->hasMany(PickupPerson::class)->where('active', true);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function dailyLogs(): HasMany
    {
        return $this->hasMany(DailyLog::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
