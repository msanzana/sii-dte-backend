<?php

namespace App\Modules\Dte\Application\UseCases\Document;

use App\Modules\Dte\Application\DTOs\PrepareDteForResendInputDto;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DispatchStatus;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Exceptions\DocumentNotFoundException;
use App\Modules\Dte\Domain\Exceptions\InvalidDocumentStateException;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class PrepareDteForResendUseCase
{
    public function __construct(
        private readonly DteDocumentRepositoryInterface $documentRepository,
        private readonly SiiDispatchRepositoryInterface $dispatchRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
    ) {
    }

    public function execute(
        PrepareDteForResendInputDto $input
    ): DteDocument {
        return DB::transaction(function () use ($input): DteDocument {

            $document = $this->documentRepository
                ->findByIdForUpdate($input->documentId);

            if (!$document) {
                throw DocumentNotFoundException::withId(
                    $input->documentId
                );
            }

            if ($document->status() !== DteStatus::SENT->value) {
                throw InvalidDocumentStateException::because(
                    "El documento {$document->id()} no está en estado sent."
                );
            }

            if (!$document->dteType()->isBoletaFamily()) {
                throw InvalidDocumentStateException::because(
                    "El documento {$document->id()} no pertenece a la familia de boletas."
                );
            }

            $latestDispatch = $this->dispatchRepository
                ->findLatestByDocumentId(
                    (int) $document->id()
                );

            if (!$latestDispatch) {
                throw InvalidDocumentStateException::because(
                    "El documento {$document->id()} no tiene un dispatch previo."
                );
            }

            if (
                $latestDispatch->dteDocumentId() !== $document->id()
                || $latestDispatch->companyId() !== $document->companyId()
            ) {
                throw InvalidDocumentStateException::because(
                    "El último dispatch no corresponde al documento {$document->id()}."
                );
            }

            if (
                $latestDispatch->status()
                !== DispatchStatus::REJECTED->value
            ) {
                throw InvalidDocumentStateException::because(
                    "El último dispatch del documento {$document->id()} no está rechazado."
                );
            }

            if ($latestDispatch->uploadStatusCode() !== 'RSC') {
                throw InvalidDocumentStateException::because(
                    "El último dispatch del documento {$document->id()} no fue rechazado por RSC."
                );
            }

            $updatedDocument = $document->withNeedsResend(
                headerPayloadPatch: $input->headerPayloadPatch,
                code: $latestDispatch->uploadStatusCode(),
                message: $latestDispatch->uploadStatusMessage()
                    ?? $latestDispatch->errorMessage()
            );

            $saved = $this->documentRepository->update(
                $updatedDocument
            );

            $this->logRepository->warning(
                channel: 'document:resend',
                message: 'Documento preparado para reproceso controlado.',
                context: [
                    'document_id' => $saved->id(),
                    'external_id' => $saved->externalId(),
                    'folio' => $saved->folio(),
                    'dispatch_id' => $latestDispatch->id(),
                    'dispatch_status' => $latestDispatch->status(),
                    'sii_code' => $latestDispatch->uploadStatusCode(),
                ],
                companyId: $saved->companyId(),
                documentId: $saved->id(),
                code: $latestDispatch->uploadStatusCode(),
            );

            return $saved;
        });
    }
}