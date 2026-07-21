<?php
namespace App\Modules\Dte\Infrastructure\Persistence\EloquentModels;

use Illuminate\Database\Eloquent\Model;
class FolioStatusEloquentModel extends Model{
    protected $table = 'folio_statuses';

    protected $fillable = [
        'code',
        'name',
        'description',
        'sort_order',
        'is_active',
    ];
    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}
