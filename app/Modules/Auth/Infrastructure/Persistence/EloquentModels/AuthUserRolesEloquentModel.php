<?php
namespace App\Modules\Auth\Infrastructure\Persistence\EloquentModels;

use Illuminate\Database\Eloquent\Model;

class AuthUserRolesEloquentModel extends Model
{
    protected $table = 'auth_user_company_roles';
    protected $fillable = [
        'user_company_access_id',
        'role_id',
    ];
}
