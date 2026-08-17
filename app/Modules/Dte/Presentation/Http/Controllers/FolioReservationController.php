<?php
namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\UseCases\Folio\DeactivateFolioReservationUseCase;
use App\Modules\Dte\Application\UseCases\Folio\GetFolioReservationUseCase;
use App\Modules\Dte\Application\UseCases\Folio\ListFolioReservationDetailsUseCase;
use App\Modules\Dte\Application\UseCases\Folio\ListFolioReservationsUseCase;
use App\Modules\Dte\Domain\Exceptions\FolioReservationNotFoundException;
use App\Modules\Dte\Presentation\Http\Requests\DeactivateFolioReservationRequest;
use App\Modules\Dte\Presentation\Http\Requests\ListFolioReservationDetailsRequest;
use App\Modules\Dte\Presentation\Http\Requests\ListFolioReservationsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;


final class FolioReservationController extends Controller
{
    public function __construct(
        private readonly ListFolioReservationsUseCase $listUseCase,
        private readonly GetFolioReservationUseCase $getUseCase,
        private readonly ListFolioReservationDetailsUseCase $detailsUseCase,
        private readonly DeactivateFolioReservationUseCase $deactivateUseCase,
    ){}

    public function index(
        ListFolioReservationsRequest $request
    ):JsonResponse
    {
        $companyId = (int) $request->attributes->get(
            'auth_company_id'
        );

        $result = $this->listUseCase->execute(
            companyId: $companyId,
            externalSystemId: $this->nullableInteger(
                $request->validated('external_system_id')
            ),
            siiDocumentTypeCode:$this->nullableString(
                $request->validated('sii_document_type_code')
            ),
            branchOfficeNumber:$this->nullableInteger(
                $request->validated('branch_office_number')
            ),
            facilityNumber: $this->nullableInteger(
                $request->validated('facility_number')
            ),
            isActive:$this->nullableBoolean(
                $request->validated('is_active')
            ),
            isCurrentlyValid:$this->nullableBoolean(
                $request->validated('is_currently_valid')
            ),
            page: (int) ($request->validated('page') ?? 1),
            perPage: (int) ($request->validated('per_page') ?? 50),
        );

        return response()->json([
            'messaje' => 'Asignaciones de rango obtenidas correctamente.',
            'data' => array_map(
                fn ($item) => $this->reservationToArray($item),
                $result->items
            ),
            'meta' => [
                'total' => $result->total,
                'page' => $result->page,
                'per_page' => $result->perPage,
                'last_page' => $result->lastPage,
            ],
        ]);
    }

    public function show(
        Request $request,
        int $reservationId
    ): JsonResponse
    {
        try {
            $item = $this->getUseCase->execute(
                companyId: (int) $request->attributes->get('auth_company_id'),
                reservationId: $reservationId
            );
            return response()->json([
                'message' => 'Asignación obtenida correctamente.',
                'data' => $this->reservationToArray($item),
            ]);
        } catch (FolioReservationNotFoundException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ],404);
        }
    }

    public function details(
        ListFolioReservationDetailsRequest $request,
        int $reservationId,
    ): JsonResponse
    {
        try {
            $result = $this->detailsUseCase->execute(
                companyId: (int) $request->attributes->get('auth_company_id'),
                reservationId: $reservationId,
                statusCode: $this->nullableString($request->validated('status_code')),
                reserved: $this->nullableBoolean($request->validated('reserved')),
                folioFrom: $this->nullableInteger($request->validated('folio_from')),
                folioTo:$this->nullableInteger($request->validated('folio_to')),
                page: (int) ($request->validated('page') ?? 1),
                perPage: (int) ($request->validated('per_page') ?? 100)
            );
            return response()->json([
                'message' => 'detalles de folio obtenidos correctamente.',
                'data' => array_map(
                    fn ($item) => [
                        'id' => $item->id,
                        'reservation_id' => $item->reservationId,
                        'caf_if' => $item->cafId,
                        'external_system_id' => $item->externalSystemId,
                        'folio_number' => $item->folioNumber,
                        'reserved' => $item->reserved,
                        'reserved_at' => $item->reservedAt,
                        'released_at' => $item->releasedAt,
                        'used_at' => $item->usedAt,
                        'expired_at' => $item->expiredAt,
                        'dte_document_id' => $item->dteDocumentId,
                        'branch_office_number' => $item->branchOfficeNumber,
                        'facility_number' => $item->facilityNumber,
                        'external_branch_code' => $item->externalBranchCode,
                        'status' => [
                            'id' => $item->folioStatusId,
                            'code' => $item->statusCode,
                            'name' => $item->statusName,
                        ],
                    ],
                    $result->items
                ),
                'meta' => [
                    'total' => $result->total,
                    'page' => $result->page,
                    'per_page' => $result->perPage,
                    'last_page' => $result->lastPage,
                ],
            ]);
        } catch (FolioReservationNotFoundException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ],404);
        }
    }
    public function deactivate(
        DeactivateFolioReservationRequest $request,
        int $reservationId,
    ): JsonResponse
    {
        try {
            $userId = $request->attributes->get(
                'auth_user_id'
            );
            $result = $this->deactivateUseCase->execute(
                companyId: (int) $request->attributes->get('auth_company_id'),
                reservationId: $reservationId,
                userId: $userId !== null ? (int) $userId : null,
                reason: (string) $request->validated('reason'),
            );
            return response()->json([
                'message' => $result->message,
                'data' => [
                    'reservation_id' => $result->reservationId,
                    'deactivated' => $result->deactivated,
                    'expired_folio_details' => $result->expiredFolioDetails,
                    'unaffected_folio_details' => $result->expiredFolioDetails,
                    'source' => $result->source,
                ],
            ]);
        } catch (FolioReservationNotFoundException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ],404);
        } catch (RuntimeException $exception)
        {
            return response()->json([
                'message' => 'No fue posible desactivar la asignación.',
                'error' => $exception->getMessage(),
            ],422);
        }
    }

    private function reservationToArray(Object $item):array
    {
        return [
            'id' => $item->id,
            'caf_id' => $item->cafId,
            'company_id' => $item->companyId,
            'external_system_id' => $item->externalSystemId,
            'sii_document_type_code' => $item->siiDocumentTypeCode,
            'branch_office_number' => $item->branchOfficeNumber,
            'facility_number' => $item->facilityNumber,
            'external_branch_code' => $item->externalBranchCode,
            'folio_range_from' => $item->folioRangeFrom,
            'folio_range_to' => $item->folioRangeTo,
            'current_folio' => $item->currentFolio,
            'assigned_quantity' => $item->assignedQuantity,
            'reserved_at' =>  $item->reservedAt,
            'expires_at' => $item->expiresAt,
            'is_currently_valid' => $item->isCurrentlyValid,
            'is_active' => $item->isActive,
            'deactivated_at' => $item->deactivatedAt,
            'deactivated_by_user_id' => $item->deactivatedByUserId,
            'deactivation_source' => $item->deactivationSource,
            'deactivation_reason' => $item->deactivationReason,
        ];
    }

    private function nullableInteger(mixed $value): ?int
    {
        return $value !== null ? (int) $value : null;
    }

    private function nullableBoolean(mixed $value): ?bool
    {
        return $value !== null ? filter_var($value, FILTER_VALIDATE_BOOLEAN) : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if($value === null)
        {
            return null;
        }
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

}
