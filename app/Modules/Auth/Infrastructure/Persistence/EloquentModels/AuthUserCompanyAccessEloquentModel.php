<?php
namespace App\Modules\Auth\Infrastructure\Persistence\EloquentModels;

use Illuminate\Database\Eloquent\Model;
class AuthUserCompanyAccessEloquentModel extends Model
{
    protected $table = 'auth_user_company_accesses';

    protected $fillable = [
        'user_id',
        'company_id',
        'is_active',
        'is_default',
        'can_select_company',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'can_select_company' => 'boolean',
    ];
}
