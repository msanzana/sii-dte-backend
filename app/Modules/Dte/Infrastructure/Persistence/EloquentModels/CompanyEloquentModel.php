<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CityEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\DteDocumentEloquentModel;
use Illuminate\Database\Eloquent\Model;

class CompanyEloquentModel extends Model
{
    protected $table = "companies";
    protected $fillable = [
        'rut',
        'rut_body',
        'rut_dv',
        'legal_name',
        'trade_name',
        'giro',
        'address',
        'city_id',
        'dte_email',
        'resolution_number',
        'resolution_date',
        'sii_environment',
        'is_active',
    ];
    protected $casts = [
        'resolution_date'   => 'date',
        'is_active' => 'boolean',
    ];
    public function city()
    {
        return $this->belongsTo(CityEloquentModel::class,'city_id');
    }
    public function documents()
    {
        return $this->hasMany(DteDocumentEloquentModel::class, 'company_id');
    }
}
