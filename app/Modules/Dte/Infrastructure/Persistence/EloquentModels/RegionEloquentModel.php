<?php

namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CountryEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\ProvinceEloquentModel;
use Illuminate\Database\Eloquent\Model;

class RegionEloquentModel extends Model
{
    protected $table = 'regions';
    protected $fillable = [
        'country_id',
        'name',
        'code',
        'is_active'
    ];
    protected $casts = [
        'is_active' => 'boolean'
    ];
    public function country()
    {
        return $this->belongsTo(CountryEloquentModel::class, 'country_id');
    }
    public function provinces()
    {
        return $this->hasMany(ProvinceEloquentModel::class,'region_id');
    }
}
