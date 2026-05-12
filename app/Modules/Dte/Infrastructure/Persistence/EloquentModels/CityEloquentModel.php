<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CompanyEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\ComuneEloquentModel;
use Illuminate\Database\Eloquent\Model;

class CityEloquentModel extends Model
{
    protected $table = "cities";
    protected $fillable = [
        'comune_id',
        'name',
        'code',
        'is_active',
    ];
    public $casts = [
        'is_active'=> 'boolean',
    ];
    public function comune()
    {
        return $this->belongsTo(ComuneEloquentModel::class,'comune_id');
    }
    public function companies()
    {
        return $this->hasMany(CompanyEloquentModel::class,'city_id');
    }
}
