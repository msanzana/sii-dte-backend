<?php
namespace  App\Modules\Auth\Infrastructure\Persistence\EloquentModels;

use Illuminate\Database\Eloquent\Model;

class AuthRoleEloquentModel extends Model
{
    protected $table ='auth_roles';
    protected $fillable =[
        'code',
        'name',
        'description',
        'is_system',
        'is_active',
    ];
    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];
}
