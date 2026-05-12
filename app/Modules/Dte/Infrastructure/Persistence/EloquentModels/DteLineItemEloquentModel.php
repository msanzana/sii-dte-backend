<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

class DteLineItemEloquentModel
{
    protected $table = 'dte_line_items';
    protected $fillable =[
        'dte_document_id',
        'line_number',
        'item_code_type',
        'item_code',
        'name',
        'description',
        'quantity',
        'unit_price',
        'discount_percent',
        'tax_exempt',
        'line_amount',
        'extra_payload',
    ];
    protected $cast = [
        'extra_payload' => 'array',
        'tax_exempt'    => 'boolean',
    ];

}
