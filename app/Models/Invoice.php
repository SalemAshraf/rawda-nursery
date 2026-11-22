<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToTenant;

    public const STATUSES = ['draft', 'sent', 'paid', 'void'];

    protected $fillable = ['tenant_id', 
        'child_id', 'description', 'issue_date', 'due_date', 'amount_minor', 'status', 'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'amount_minor' => 'integer',
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paidMinor(): int
    {
        return (int) $this->payments()->sum('amount_minor');
    }

    public function outstandingMinor(): int
    {
        return max(0, $this->amount_minor - $this->paidMinor());
    }

    public function isOverdue(string $today): bool
    {
        return in_array($this->status, ['sent']) && $this->due_date->toDateString() < $today;
    }
}
