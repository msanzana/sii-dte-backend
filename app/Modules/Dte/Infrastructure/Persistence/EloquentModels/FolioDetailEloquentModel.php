<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\DteDocumentEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\FolioDetailEventEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\FolioReservationEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\FolioStatusEloquentModel;
use Illuminate\Database\Eloquent\Model;

class FolioDetailEloquentModel extends Model
{
    protected $table = 'folio_details';

    protected $fillable = [
        'folio_reservation_id',
        'company_id',
        'external_system_id',
        'caf_id',
        'sii_document_type_code',
        'branch_office_number',
        'facility_number',
        'external_branch_code',
        'folio_number',
        'folio_status_id',
        'reserved',
        'reserved_at',
        'released_at',
        'used_at',
        'dte_document_id',
    ];
    protected $casts = [
        'folio_reservation_id' => 'integer',
        'company_id' => 'integer',
        'external_system_id' => 'integer',
        'caf_id' => 'integer',

        'branch_office_number' => 'integer',
        'facility_number' => 'integer',

        'folio_number' => 'integer',
        'folio_status_id' => 'integer',
        'dte_document_id' => 'integer',

        'reserved' => 'boolean',
        'reserved_at' => 'datetime',
        'released_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(
            FolioReservationEloquentModel::class,
            'folio_reservation_id',
        );
    }
    public function status()
    {
        return $this->belongsTo(
            FolioStatusEloquentModel::class,
            'folio_status_id',
        );
    }
    public function events()
    {
        return $this->hasMany(
            FolioDetailEventEloquentModel::class,
            'folio_detail_id',
        );
    }
    public function document()
    {
        return $this->belongsTo(
            DteDocumentEloquentModel::class,
            'dte_document_id',
        );
    }
    
}