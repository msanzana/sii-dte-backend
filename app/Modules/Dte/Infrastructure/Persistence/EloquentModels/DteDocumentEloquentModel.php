<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CityEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CompanyEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\DteLineItemEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\DteReferenceEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\ExternalSystemEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\FolioDetailEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\FolioReservationEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\SiiCafEloquentModel;
use Illuminate\Database\Eloquent\Model;


class DteDocumentEloquentModel extends Model
{
    protected $table = 'dte_documents';
    protected $fillable = [
        'external_id',
        'company_id',

        'external_system_id',
        'caf_id',
        'folio_reservation_id',
        'branch_office_number',
        'facility_number',
        'external_branch_code',


        'dte_type',
        'folio',
        'issue_date',
        'status',
        'sii_environment',

        'receiver_document',
        'receiver_name',
        'receiver_giro',
        'receiver_address',
        'receiver_city_id',
        'receiver_email',

        'net_amount',
        'exempt_amount',
        'tax_amount',
        'total_amount',

        'header_payload',
        'totals_payload',
        'raw_input',
        'validation_warnings',

        'unsigned_xml_path',
        'signed_xml_path',
        'ted_xml',

        'last_error_code',
        'last_error_message',

        'queued_at',
        'sent_at',
        'accepted_at',
        'rejected_at',
    ];
    protected $casts = [
        'company_id' => 'integer',
        'external_system_id' => 'integer',
        'caf_id' => 'integer',
        'folio_reservation_id' => 'integer',
        'branch_office_number' => 'integer',
        'facility_number' => 'integer',
        'issue_date' => 'date',

        'net_amount' => 'decimal:2',
        'exempt_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',

        'header_payload' => 'array',
        'totals_payload' => 'array',
        'raw_input' => 'array',
        'validation_warnings' => 'array',

        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];
    public function company()
    {
        return $this->belongsTo(CompanyEloquentModel::class, 'company_id');
    }
    public function externalSystem()
    {
        return $this->belongsTo(ExternalSystemEloquentModel::class,'external_system_id');
    }
    public function caf()
    {
        return $this->belongsTo(SiiCafEloquentModel::class, 'caf_id');
    }
    public function folioReservation()
    {
        return $this->belongsTo(FolioReservationEloquentModel::class,'folio_reservation_id');
    }
    public function folioDetail()
    {
        return $this->hasOne(FolioDetailEloquentModel::class,'dte_document_id');
    }
    public function receiverCity()
    {
        return $this->belongsTo(CityEloquentModel::class, 'receiver_city_id');
    }
    public function items()
    {
        return $this->hasMany(DteLineItemEloquentModel::class, 'dte_document_id');
    }
    public function references()
    {
        return $this->hasMany(DteReferenceEloquentModel::class, 'dte_document_id');
    }
}
