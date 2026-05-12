<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;

use App\Modules\Dte\Domain\Entities\City;

interface CityRepositoryInterface
{
    public function findById(int $id):City;
    public function existsActiveById(int $id):bool;
}
