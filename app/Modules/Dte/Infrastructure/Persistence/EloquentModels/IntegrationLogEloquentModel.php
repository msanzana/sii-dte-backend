<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use Illuminate\Database\Eloquent\Model;
class IntegrationLogEloquentModel extends Model
{
    protected $table = 'integration_logs';
    protected $fillable = [
        'company_id',
        'dte_document_id',
        'channel',
        'level',
        'code',
        'message',
        'context',
        'occurred_at',
    ];
    protected $casts = [
        'context' => 'array',
        'occurred_at' => 'datetime',
    ];
}
