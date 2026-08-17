<?php
namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;


use App\Modules\Dte\Domain\RepositoryContracts\LocationRepositoryInterface;
use App\Modules\Dte\Domain\ValueObjects\LocationSummary;
use Illuminate\Support\Facades\DB;


final class EloquentLocationRepository implements LocationRepositoryInterface
{
    public function findSummaryByCityId(int $cityId): ? LocationSummary
    {
        $row = DB::table('cities as c')
                ->join('comunes as co', 'co.id','=','c.commune_id')
                ->select([
                    'c.id AS city_id',
                    'c.name AS city_name',
                    'co.name as comune_name',
                ])
                ->where('c.id',$cityId)
                ->where('c.is_active',true)
                ->where('co.is_active',true)
                ->first();
        if(!$row)
        {
            return null;
        }

        return new LocationSummary(
            cityId: (int) $row->city_id,
            cityName: (string) $row->city_name,
            comuneName: (string) $row->comune_name,
        );
    }
}
