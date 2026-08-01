<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CompanyEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\ExternalSystemEloquentModel;
use Illuminate\Database\Eloquent\Model;

class SiiCafEloquentModel extends Model
{
    protected $table = 'sii_cafs';
    protected $fillable = [
        'company_id',
        'dte_type',
        'folio_start',
        'folio_end',
        'last_assigned_folio',
        'caf_xml_path',
        'private_key_pem_encrypted',
        'public_key_pem',
        'authorized_at',
        'is_active',

        // Campos incorporados por la Gran tarea post-término.
        'external_system_id',
        'requested_folios_count',
        'available_folios_count',
        'reserved_folios_count',
        'used_folios_count',
    ];
    protected $casts = [
        'company_id' => 'integer',
        'dte_type' => 'integer',
        'folio_start' => 'integer',
        'folio_end' => 'integer',
        'last_assigned_folio' => 'integer',

        'external_system_id' => 'integer',
        'requested_folios_count' => 'integer',
        'available_folios_count' => 'integer',
        'reserved_folios_count' => 'integer',
        'used_folios_count' => 'integer',

        'authorized_at' => 'date',
        'is_active' => 'boolean',
    ];
    public function company()
    {
        return $this->belongsTo(CompanyEloquentModel::class, 'company_id');
    }
    public function externalSystem()
{
    return $this->belongsTo(
        ExternalSystemEloquentModel::class,
        'external_system_id'
    );
}
}
