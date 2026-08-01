<?php
namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\DTOs\FindAvailableFoliosInputDto;
use App\Modules\Dte\Application\DTOs\ReleaseReservedFolioInputDto;
use App\Modules\Dte\Application\DTOs\ReserveFoliosInputDto;
use App\Modules\Dte\Application\UseCases\Folio\FindAvailableFoliosUseCase;
use App\Modules\Dte\Application\UseCases\Folio\ReleaseReservedFolioUseCase;
use App\Modules\Dte\Application\UseCases\Folio\ReserveFoliosUseCase;
use App\Modules\Dte\Presentation\Http\Requests\FindAvailableFoliosRequest;
use App\Modules\Dte\Presentation\Http\Requests\ReleaseReservedFolioRequest;
use App\Modules\Dte\Presentation\Http\Requests\ReserveFoliosRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;

final class FolioController extends Controller
{
    public function __construct(
        private readonly FindAvailableFoliosUseCase $findAvailableFoliosUseCase,
        private readonly ReserveFoliosUseCase $reserveFoliosUseCase,
        private readonly ReleaseReservedFolioUseCase $releaseReservedFolioUseCase
    ){}

    public function available(
        FindAvailableFoliosRequest $request
    ): JsonResponse
    {
        $companyId =(int) $request->attributes->get(
            'auth_company_id'
        );

        try {
            $result = $this->findAvailableFoliosUseCase->execute(
                input: new FindAvailableFoliosInputDto(
                    companyId: $companyId,
                    externalSystemId: (int) $request->validated('external_system_id'),
                    siiDocumentTypeCode: (string) $request->validated('sii_document_type_code'),
                    branchOfficeNumber: $this->nullableInteger($request->validated('branch_office_number')),
                    facilityNumber: $this->nullableInteger($request->validated('facility_number')),
                    externalBranchCode: $this->nullableString($request->validated('external_branch_code'))
                ),
                previewLimit: (int) ($request->validated('preview_limit') ?? 10)
            );
            return response()->json([
                'message' => 'Disponibilidad obtenida correctamente.',
                'data' => [
                    'available_quantity' => $result->availableQuantity,
                    'folios_preview' => $result->foliosPreview,
                ],
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => 'No fue posible consultar los folios.',
                'error' => $exception->getMessage(),
            ],422);
        }
    }
    public function reserve(
        ReserveFoliosRequest $request
    ):JsonResponse
    {
        $companyId = (int) $request->attributes->get(
            'auth_company_id'
        );
        try {
            $result = $this->reserveFoliosUseCase->execute(
                new ReserveFoliosInputDto(
                    companyId: $companyId,
                    externalSystemId: (int) $request->validated('external_system_id'),
                    siiDocumentTypeCode: (string) $request->validated('sii_document_type_code'),
                    requestedQuantity: (int) $request->validated('requested_quantity'),
                    branchOfficeNumber: $this->nullableInteger($request->validated('branch_office_number')),
                    facilityNumber: $this->nullableInteger($request->validated('facility_number')),
                    externalBranchCode: $this->nullableString($request->validated('external_branch_code')),
                ),

            );

            return response()->json([
                'message' => match ($result->status){
                    'success' => 'Folios reservados correctamente.',
                    'partial' => 'La reserva fue realizada parcialmente.',
                    default => 'No fue posible reservar folios.',
                },
                'data' =>[
                    'requested_quantity' => $result->requestedQuantity,
                    'reserved_quantity' => $result->reservedQuantity,
                    'available_quantity_after' => $result->availableQuantityAfter,
                    'folios' => $result->folios,
                    'status' => $result->status,
                    'warning' => $result->warning,
                ],
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => 'MNo fue posible reservar los folios.',
                'error' => $exception->getMessage(),
            ],422);
        }
    }

    public function release(
        ReleaseReservedFolioRequest $request
    ):JsonResponse
    {
        $companyId = (int) $request->attributes->get(
            'auth_company_id'
        );
        $userId = $request->attributes->get(
            'auth_user_id'
        );
        try {
            $result = $this->releaseReservedFolioUseCase->execute(
                new ReleaseReservedFolioInputDto(
                    companyId: $companyId,
                    externalSystemId: (int) $request->validated('external_system_id'),
                    siiDocumentTypeCode: (string) $request->validated('sii_document_type_code'),
                    folioNumber: (int) $request->validated('folio_number'),
                    branchOfficeNumber: $this->nullableInteger($request->validated('branch_office_number')),
                    facilityNumber: $this->nullableInteger($request->validated('facility_number')),
                    externalBranchCode: $this->nullableString($request->validated('external_branch_code')),
                    reason: $this->nullableString($request->validated('reason')),
                    userId: $userId !== null ? (int) $userId : null,
                )
            );
            return response()->json([
                'message' => $result->message,
                'data' => [
                    'folio_number' => $result->folioNumber,
                    'released' => $result->released,
                    'new_status' => $result->newStatus,
                ],
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => 'no fue posible liberar el folio.',
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function nullableInteger(mixed $value): ?int
    {
        return $value !== null
            ? (int) $value
            : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if($value === null)
        {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== ''
            ?$normalized
            :null;
    }
}
