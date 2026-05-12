<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CityEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\ProvinceEloquentModel;
use Illuminate\Database\Eloquent\Model;



class ComuneEloquentModel extends Model
{
    protected $table = "comunes";
    protected $fillable = [
        'region_id',
        'name',
        'code',
        'is_active',
    ];
    protected  $casts = [
        'is_active' => 'boolean',
    ];
    public function produce()
    {
        return $this->belongsTo(ProvinceEloquentModel::class, 'province_id');
    }
    public function cities()
    {
        return $this->hasMany(CityEloquentModel::class,'city_id');
    }

}
