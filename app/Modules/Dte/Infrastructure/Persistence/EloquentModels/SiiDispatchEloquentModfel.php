<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\DteDocumentEloquentModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiiDispatchEloquentModel extends Model
{
    protected $table = 'sii_dispatches';
    protected $fillable = [
        'batch_uuid',
        'company_id',
        'dte_document_id',
        'environment',
        'transport_type',
        'status',
        'track_id',
        'request_identifier',
        'request_path',
        'request_headers',
        'request_body_path',
        'response_http_status',
        'response_body',
        'upload_status_code',
        'upload_status_message',
        'retry_count',
        'next_retry_at',
        'error_message',
        'sent_at',
        'last_polled_at',
        'processed_at',
    ];
    protected $casts = [
        'request_headers' => 'array',
        'retry_count' => 'integer',
        'last_polled_at' => 'datetime',
        'processed_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(DteDocumentEloquentModel::class, 'dte_document_id');
    }
}
