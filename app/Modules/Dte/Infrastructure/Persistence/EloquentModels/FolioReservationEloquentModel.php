<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\ExternalSystemEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\SiiCafEloquentModel;
use Illuminate\Database\Eloquent\Model;

class FoliosReservationEloquentModel extends Model
{
    protected $table = 'folio_reservations';

    protected $fillable = [
        'caf_id',
        'external_system_id',
        'company_id',
        'sii_document_type_code',
        'branch_office_number',
        'facility_number',
        'external_branch_code',
        'folio_range_from',
        'folio_range_to',
        'current_folio',
        'reserved_quantity',
        'reserved_at',
        'requires_at',
        'is_currently_valid',
        'is_active'
    ];

    protected $casts = [
        'caf_id' => 'integer',
        'external_system_id' => 'integer',
        'company_id' => 'integer',

        'branch_office_number' => 'integer',
        'facility_number' => 'integer',
        
        'folio_range_from' => 'integer',
        'folio_range_to' => 'integer',
        'current_folio' => 'integer',
        'reserved_quantity' => 'integer',

        'reserved_at' => 'datetime',
        'requires_at' => 'datetime',

        'is_currently_valid' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function caf()
    {
        return $this->belongsTo(
            SiiCafEloquentModel::class,
            'caf_id'
        );
    }
    public function externalSystem()
    {
        return $this->belongsTo(
            ExternalSystemEloquentModel::class,
            'external_system_id',
        );
    }
    public function details()
    {
        return $this->hasMany(
            FolioDetailEloquentModel::class,
            'folio_reservation_id'
        );
    }
}