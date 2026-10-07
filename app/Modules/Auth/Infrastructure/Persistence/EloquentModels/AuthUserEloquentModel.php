<?php

namespace App\Modules\Auth\Infrastructure\Persistence\EloquentModels;

use Illuminate\Database\Eloquent\Model;

class AuthUserEloquentModel extends Model
{
    protected $table = 'auth_users';

    protected $fillable = [
        'full_name',
        'email',
        'account_type',
        'login_uuid',
        'password_hash',
        'is_active',
        'is_visible',
        'last_login_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'last_login_at' => 'datetime',
    ];
}