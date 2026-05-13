<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use Illuminate\Database\Eloquent\Model;

class SystemSettingEloquentModel extends Model
{
    protected $table = 'system_settings';
    protected $fillable = [
        'key',
        'value',
        'description',
    ];
    protected $casts = [
        'value' => 'array',
    ];
}
