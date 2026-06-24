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
        'pfx_sha256',
        'metadata_hash',
        'certificate_fingerprint_sha1',
        'has_private_key',
        'is_default',
        'is_active',
        'last_validity_check_at',
        'last_validity_status',
    ];
    protected $casts = [
        'has_private_key' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'last_validity_check_at' => 'datetime',
    ];
    public function company():BelongsTo
    {
        return $this->belongsTo(CompanyEloquentModel::class, 'company_id');
    }

}
