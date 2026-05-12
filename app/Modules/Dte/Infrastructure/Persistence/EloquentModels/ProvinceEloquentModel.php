<?php

namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\ComuneEloquentModel;
use Illuminate\Database\Eloquent\Model;


class ProvinceEloquentModel extends Model
{
    protected $table = "provinces";
    protected $fillable = [
        'region_id',
        'name',
        'code',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];
    public function region()
    {
        return $this->belongsTo(RegionEloquentModel::class);
    }
    public function comunes()
    {
        return $this->hasMany(ComuneEloquentModel::class);
    }
}
