<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use Illuminate\Database\Eloquent\Model;
class DteReferenceEloquentModel extends Model
{
    protected $table = 'dte_references';
    protected $fillable = [
        'dte_document_id',
        'line_number',
        'referenced_folio',
        'referenced_issue_date',
        'referenced_code',
        'reason',
        'extra_payload',
    ];
    protected $casts = [
        'referenced_issue_date' => 'date',
        'extra_payload' => 'array',
    ];
}
