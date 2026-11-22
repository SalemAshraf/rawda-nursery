<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = ['name', 'slug', 'timezone', 'currency', 'setup_pending'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Child::class);
    }

    // Local calendar date (Y-m-d) in the tenant's timezone. Attendance and
    // "today" follow the nursery's calendar, never UTC.
    public function today(): string
    {
        return now($this->timezone)->toDateString();
    }
}
