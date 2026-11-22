<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One access and refresh token pair, stored as hashes. Rows of one connection share a family; refreshing rotates the row. Deliberately not tenant-scoped by a global scope: the token endpoint has no signed-in user. */
class OauthToken extends Model
{
    protected $guarded = [];

    protected $hidden = ['access_hash', 'refresh_hash'];

    protected function casts(): array
    {
        return ['access_expires_at' => 'datetime', 'refresh_expires_at' => 'datetime', 'rotated_at' => 'datetime', 'revoked_at' => 'datetime', 'last_used_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
