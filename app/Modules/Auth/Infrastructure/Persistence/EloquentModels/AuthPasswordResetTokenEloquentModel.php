<?php

namespace App\Modules\Auth\Infrastructure\Persistence\EloquentModels;

use Illuminate\Database\Eloquent\Model;

class AuthPasswordResetTokenEloquentModel extends Model
{
    protected $table = 'auth_password_reset_tokens';

    protected $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
        'used_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];
}