<?php
namespace App\Modules\Auth\Infrastructure\Persistence\EloquentModels;

use Illuminate\Database\Eloquent\Model;
class AuthUserEloquentModel extends Model
{
    protected $table ='auth_users';

    protected $fillable = [
        'full_name',
        'email',
        'password_hash',
        'is_active',
        'last_login_at',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime'
    ];
}
