<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use Illuminate\Database\Eloquent\Model;

class FolioDetailEventEloquentModel extends Model
{
    protected $table = 'folio_detail_events';

    public $timestamps = false;

    protected $fillable = [
        'folio_detail_id',
        'company_id',
        'external_system_id',
        'branch_office_number',
        'facility_number',
        'event_code',
        'from_status_code',
        'to_status_code',
        'message',
        'user_id',
        'payload_json',
        'created_at',
    ];
    protected $casts = [
        'folio_detail_id' => 'integer',
        'company_id' => 'integer',
        'external_system_id' => 'integer',
        'branch_office_number' => 'integer',
        'facility_number' => 'integer',
        'user_id' => 'integer',
        'created_at' => 'datetime',
    ];
    public function folioDetail()
    {
        return $this->belongsTo(
            FolioDetaiLEloquentModel::class,
            'folio_detail_id',
        );
    }
}
