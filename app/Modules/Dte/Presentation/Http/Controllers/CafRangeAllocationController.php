<?php
namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\DTOs\AssignCafRangeInputDto;
use App\Modules\Dte\Application\UseCases\Folio\AssignCafRangeUseCase;
use App\Modules\Dte\Domain\Exceptions\DomainException;
use App\Modules\Dte\Presentation\Http\Requests\AssignCafRangeRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;

final class CafRangeAllocationController extends Controller
{
    public function __construct(
        private readonly AssignCafRangeUseCase $assignCafRangeUseCase,
    ){}

    public function store(
        AssignCafRangeRequest $request,
        int $cafId
    ): JsonResponse
    {
        $companyId = (int) $request->attributes->get('auth_company_id');
        $userId = $request->attributes->get('auth_user_id');
        $data = $request->validated();

        try {
            $result = $this->assignCafRangeUseCase->execute(
                new AssignCafRangeInputDto(
                    companyId: $companyId,
                    cafId: $cafId,
                    externalSystemId: (int) $data['external_system_id'],
                    folioRangeFrom: (int) $data['folio_range_from'],
                    folioRangeTo: (int) $data['folio_range_to'],
                    branchOfficeNumber: isset($data['branch_office_number']) ? (int) $data['branch_office_number'] : null,
                    facilityNumber: isset($data['facility_number']) ? (int) $data['facility_number'] : null,
                    externalBranchCode: isset($data['external_branch_code']) 
                                        && trim((string) $data['external_branch_code']) !== ''
                                        ? trim((string) $data['external_branch_code'])
                                        :null,
                    expiresAt: isset($data['expires_at']) ? (string) $data['expires_at'] : null,
                    userId: $userId !== null
                                ? (int) $userId
                                : null,

                )
            );
            return response()->json([
                'messaje' => 'Rango CAF asignado correctamente',
                'data' => [
                    'reservation_id' => $result->reservationId,
                    'caf_id' => $result->cafId,
                    'company_id' => $result->companyId,
                    'external_system_id' => $result->externalSystemId,
                    'dte_type' => $result->dteType,
                    'folio_range_from' => $result->folioRangeFrom,
                    'folio_range_to' => $result->folioRangeTo,
                    'assigned_quantity' => $result->assignedQuantity,
                    'created_folio_details' => $result->createdFolioDetails,
                    'branch_office_number' => $result->branchOfficeNumber,
                    'facility_number' => $result->facilityNumber,
                    'external_branch_code' => $result->externalBranchCode,
                    'expires_at' => $result->expiresAt,
                    'status' => $result->status,

                ],
            ],201);
        } catch (DomainException|RuntimeException $exception) {
            return response()->json([
                'message' => 'No fue posible asignar el rango CAF.',
                'error' => $exception->getMessage(),
            ],422);
        }
    }
}