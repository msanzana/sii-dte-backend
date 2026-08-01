<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CompanyEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\FolioReservationEloquentModel;
use Illuminate\Database\Eloquent\Model;

class ExternalSystemEloquentModel extends Model
{
    protected $table ='external_systems';
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'is_active',
    ];
    protected $casts = [
        'company_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(
            CompanyEloquentModel::class,
            'company_id'
        );
    }
    public function folioReservations()
    {
        return $this->hasMany(
            FolioReservationEloquentModel::class,
            'external_system_id'
        );
    }
}