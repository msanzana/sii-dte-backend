<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CityEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CompanyEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\DteLineItemEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\DteReferenceEloquentModel;
use Illuminate\Database\Eloquent\Model;


class DteDocumentEloquentModel extends Model
{
    protected $table = 'dte_documents';
    protected $fillable = [
        'external_id',
        'company_id',
        'dte_type',
        'folio',
        'issue_date',
        'status',
        'sii_environment',
        'receive_document',
        'receiver_name',
        'receiver_giro',
        'receiver_address',
        'receiver_city_id',
        'receiver_email',
        'net_amount',
        'exempt_amount',
        'tax_amount',
        'total_amount',
        'header_amount',
        'totals_amount',
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
    protected $cast = [
        'issue_date' => 'date',
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
