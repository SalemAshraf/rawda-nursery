<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A client (Claude) that registered itself through dynamic client registration. Public client: no secret. */
class OauthClient extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['redirect_uris' => 'array'];
    }
}
