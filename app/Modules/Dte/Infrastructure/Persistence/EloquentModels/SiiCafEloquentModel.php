<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CompanyEloquentModel;
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
        'public_key_pen',
        'authorized_at',
        'is_active',
    ];
    protected $casts = [
        'authorized_at' => 'date',
        'is_active' => 'boolean',
    ];
    public function company()
    {
        return $this->belongsTo(CompanyEloquentModel::class, 'company_id');
    }
}
