<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Admission extends Model
{
    use BelongsToTenant;

    public const OPEN_STATUSES = ['inquiry', 'tour', 'waitlist', 'closed'];

    protected $fillable = ['tenant_id', 'first_name', 'last_name', 'dob', 'guardian_name', 'guardian_phone', 'guardian_relation', 'classroom_id', 'status', 'follow_up_on', 'notes', 'child_id'];

    protected $casts = ['dob' => 'date', 'follow_up_on' => 'date'];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}
