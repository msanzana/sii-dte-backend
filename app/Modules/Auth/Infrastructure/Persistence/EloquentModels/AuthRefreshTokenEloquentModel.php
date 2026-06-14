<?php

namespace App\Modules\Auth\Infrastructure\Persistence\EloquentModels;

use Illuminate\Database\Eloquent\Model;

class AuthRefreshTokenEloquentModel extends Model
{
    protected $table = 'auth_refresh_tokens';

    protected $fillable = [
        'user_id',
        'company_id',
        'token_hash',
        'user_agent',
        'ip_address',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];
}
