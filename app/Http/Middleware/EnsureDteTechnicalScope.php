<?php

namespace App\Http\Middleware;

use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioReservationRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use Closure;
use Illuminate\Http\Request;

final class EnsureDteTechnicalScope
{
    public function __construct(
        private readonly DteDocumentRepositoryInterface $dteDocumentRepository,
        private readonly SiiDispatchRepositoryInterface $siiDispatchRepository,
        private readonly FolioReservationRepositoryInterface $folioReservationRepository,
    ) {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        if (
            $request->attributes->get('auth_account_type')
            !== AuthUser::ACCOUNT_TYPE_TECHNICAL
        ) {
            return $next($request);
        }

        $companyId = (int) $request->attributes->get('auth_company_id');
        $externalSystemId = (int) $request->attributes->get(
            'auth_external_system_id'
        );

        if ($companyId <= 0 || $externalSystemId <= 0) {
            return response()->json([
                'message' => 'El ámbito técnico DTE no es válido.',
            ], 403);
        }

        if ($request->exists('external_system_id')) {
            $providedExternalSystemId = $request->input('external_system_id');

            if (
                $providedExternalSystemId !== null
                && (
                    !is_numeric($providedExternalSystemId)
                    || (int) $providedExternalSystemId !== $externalSystemId
                )
            ) {
                return response()->json([
                    'message' => 'El sistema externo solicitado no pertenece al ámbito técnico autenticado.',
                ], 403);
            }
        }

        // En una sesión técnica el sistema externo siempre lo determina el servidor.
        // También completa los FormRequest que actualmente exigen external_system_id.
        $request->merge([
            'external_system_id' => $externalSystemId,
        ]);

        $documentId = $this->positiveRouteId($request, 'documentId');

        if ($documentId !== null) {
            $document = $this->dteDocumentRepository->findById($documentId);

            if (
                $document === null
                || $document->companyId() !== $companyId
                || $document->externalSystemId() !== $externalSystemId
            ) {
                return $this->scopeNotFound();
            }
        }

        $dispatchId = $this->positiveRouteId($request, 'dispatchId');

        if ($dispatchId !== null) {
            $dispatch = $this->siiDispatchRepository->findById($dispatchId);

            if (
                $dispatch === null
                || $dispatch->companyId() !== $companyId
                || $dispatch->dteDocumentId() === null
            ) {
                return $this->scopeNotFound();
            }

            $dispatchDocument = $this->dteDocumentRepository->findById(
                $dispatch->dteDocumentId()
            );

            if (
                $dispatchDocument === null
                || $dispatchDocument->companyId() !== $companyId
                || $dispatchDocument->externalSystemId() !== $externalSystemId
            ) {
                return $this->scopeNotFound();
            }
        }

        $reservationId = $this->positiveRouteId($request, 'reservationId');

        if ($reservationId !== null) {
            $reservation = $this->folioReservationRepository->findByCompanyAndId(
                $companyId,
                $reservationId,
            );

            if (
                $reservation === null
                || $reservation->externalSystemId() !== $externalSystemId
            ) {
                return $this->scopeNotFound();
            }
        }

        return $next($request);
    }

    private function positiveRouteId(Request $request, string $parameter): ?int
    {
        $value = $request->route($parameter);

        if ($value === null || !is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private function scopeNotFound(): mixed
    {
        // 404 evita revelar que el recurso existe en otra empresa/sistema.
        return response()->json([
            'message' => 'El recurso DTE no existe en el ámbito técnico autenticado.',
        ], 404);
    }
}
