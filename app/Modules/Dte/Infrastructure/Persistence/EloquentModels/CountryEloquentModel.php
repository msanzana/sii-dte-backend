<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\RegionEloquentModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CountryEloquentModel extends Model
{
    protected $table = 'countries';
    protected $fillable = [
        'name',
        'iso_code_2',
        'iso_code_3',
        'phone_code',
        'is_active',

    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];
    public function states(): HasMany
    {
        return $this->hasMany(RegionEloquentModel::class, 'country_id');
    }
}
