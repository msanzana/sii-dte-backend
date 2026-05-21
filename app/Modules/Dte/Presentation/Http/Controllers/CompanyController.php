<?php
namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\DTOs\CreateCompanyInputDto;
use App\Modules\Dte\Application\UseCases\Company\CreateCompanyUseCase;
use App\Modules\Dte\Domain\Exceptions\DomainException;
use App\Modules\Dte\Presentation\Http\Requests\StoreCompanyRequest;
use App\Modules\Dte\Presentation\Http\Resources\CompanyResource;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{

    public function __construct(
        private readonly CreateCompanyUseCase $createCompanyUseCase
    )
    {}

    public function create(StoreCompanyRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $result = $this->createCompanyUseCase->execute(
                new CreateCompanyInputDto(
                    rut: $data['rut'],
                    rutBody: $data['rut_body'],
                    rutDv: $data['rut_dv'],
                    legalName: $data['legal_name'],
                    tradeName: $data['trade_name'] ?? null,
                    giro: $data['giro'] ?? null,
                    address: $data['address'],
                    cityId: (int) $data['city_id'],
                    dteEmail: $data['dte:email'] ?? null,
                    resolutionNumber: $data['resolution_number'] ?? null,
                    resolutionDate: $data['resolution_date'] ?? null,
                    siiEnvironment: $data['sii_environment'],
                    isActive: (bool) ($data['is_active'] ?? true),
                )
            );

            return response()->json([
                'message' => 'Empresa registrada.',
                'date' => new CompanyResource((object) [
                    'id' => $result->id,
                    'rut' => $result->rut,
                    'legalName' => $result->legalName,
                    'cityId' => $result->cityId,
                    'siiEnvironment' => $result->siiEnvironment,
                    'isActive' => $result->isActive,
                ]),
            ],201);
        } catch (DomainException $e) {
            return response()->json([
                'message' => 'no fue posible registrar la emnpresa.',
                'error' => $e->getMessage(),
            ],422);
        }
    }

}
