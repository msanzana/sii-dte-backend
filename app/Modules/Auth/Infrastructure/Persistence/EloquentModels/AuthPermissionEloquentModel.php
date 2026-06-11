<?php
namespace App\Modules\Auth\Infrastructure\Persistence\EloquentModels;

use Illuminate\Database\Eloquent\Model;
class AuthPermissionEloquentModel extends Model
{
    protected $table = 'auth_permissions';

    protected $fillable = [
        'code',
        'name',
        'module',
        'description',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
