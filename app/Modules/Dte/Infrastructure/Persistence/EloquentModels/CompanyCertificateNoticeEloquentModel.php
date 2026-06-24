<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use Illuminate\Database\Eloquent\Model;

class CompanyCertificateNoticeEloquentModel extends Model
{
    protected $table = 'company_certificate_notices';

    protected $fillable = [
        'company_id',
        'user_id',
        'certificate_id',
        'source',
        'type',
        'code',
        'tittle',
        'message',
        'notice_date',
        'notice_time',
        'emitted_at',
        'is_read',
        'is_active',
    ];

    protected $casts = [
        'notice_date' => 'date',
        'emitted_at' => 'datetime',
        'is_read' => 'boolean',
        'is_active' => 'boolean',
    ];


}
