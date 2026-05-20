<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CompanyEloquentModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiiCertificateEloquentModel extends Model
{
    protected $table = 'sii_certificates';
    protected $fillable =[
        'company_id',
        'alias',
        'pfx_path',
        'pfx_password_encrypted',
        'serial_number',
        'subject_name',
        'issuer_name',
        'valid_from',
        'valid_to',
        'is_default',
        'is_active',
    ];
    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];
    public function company():BelongsTo
    {
        return $this->belongsTo(CompanyEloquentModel::class, 'company_id');
    }

}
