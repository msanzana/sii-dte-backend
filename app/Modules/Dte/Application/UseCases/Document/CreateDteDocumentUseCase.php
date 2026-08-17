<?php
namespace App\Modules\Dte\Application\UseCases\Document;

use App\Modules\Dte\Application\DTOs\CreateDteDocumentInputDto;
use App\Modules\Dte\Application\DTOs\CreateDteDocumentResultDto;
use App\Modules\Dte\Application\Services\DteDocumentApplicationService;
use App\Modules\Dte\Application\Services\RecalculateCafCountersService;
use App\Modules\Dte\Application\Services\ResolveReservedFolioForDteService;
use App\Modules\Dte\Domain\Exceptions\CityNotFoundException;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\Exceptions\DteReservedFolioException;
use App\Modules\Dte\Domain\Exceptions\ServicePausedException;
use App\Modules\Dte\Domain\RepositoryContracts\CityRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailEventRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioReservationRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioStatusRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SystemSettingRepositoryInterface;
use App\Modules\Dte\Domain\Services\DteDocumentValidationDomainService;
use Illuminate\Support\Facades\DB;



final class CreateDteDocumentUseCase
{
    public function __construct(
        private readonly DteDocumentApplicationService $documentApplicationService,
        private readonly DteDocumentValidationDomainService $validationDomainService,
        private readonly DteDocumentRepositoryInterface $documentRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly CityRepositoryInterface $cityRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
        private readonly SystemSettingRepositoryInterface $settingRepository,
        private readonly ResolveReservedFolioForDteService $resolveReservedFolioForDteService,
        private readonly FolioDetailRepositoryInterface $folioDetailRepository,
        private readonly FolioStatusRepositoryInterface $folioStatusRepository,
        private readonly FolioDetailEventRepositoryInterface $folioDetailEventRepository,
        private readonly FolioReservationRepositoryInterface $folioReservationRepository,
        private readonly RecalculateCafCountersService $recalculateCafCountersService,
    )
    {}
    public function execute(
        CreateDteDocumentInputDto $input
    ): CreateDteDocumentResultDto
    {
        $paused = (bool) $this->settingRepository->get(
            config('dte.service_state.paused_key'),
            false
        );
        $reasonData = $this->settingRepository->get(
            config('dte.service_state.pause_reason_key'),
            null
        );
        if ($paused) {
            $reason = is_array($reasonData) ? ($reasonData['reason'] ?? 'Sin razón registrada'): 'Sin razón registrada';
            throw ServicePausedException::because($reason);
        }
        if (!$this->companyRepository->existsActiveById($input->companyId)
        ) {
            throw CompanyNotFoundException::withId($input->companyId);
        }
        if ($input->receiver->cityId !== null && !$this->cityRepository->existsActiveById(
                $input->receiver->cityId
            )
        ) {
            throw CityNotFoundException::withId($input->receiver->cityId);
        }

        return DB::transaction(function () use ($input)
        {
            /*
            * Si existe sistema externo:
            * busca y bloquea el folio reservado.
            *
            * Si es flujo interno:
            * devuelve NULL.
            */
            $resolvedFolio = $this->resolveReservedFolioForDteService->execute($input);
            $domainDocument = $this->documentApplicationService->buildDomainDocument(
                        dto: $input,
                        resolvedFolio: $resolvedFolio
                    );
            $this->validationDomainService->validateForCreation($domainDocument);
            /*
            * Primero guardamos el documento.
            * Todavía estamos dentro de la misma transacción.
            */
            $saved = $this->documentRepository->create($domainDocument);
            /*
            * Solamente existe para flujo sistema externo.
            */
            if ($resolvedFolio !== null)
            {
                $assignedStatus = $this->folioStatusRepository->findByCode('assigned');

                if (!$assignedStatus) {
                    throw new \RuntimeException('No existe el estado de folio assigned.');
                }

                $assigned = $this->folioDetailRepository->assignToDocument(
                            folioDetailId:$resolvedFolio->folioDetailId,
                            assignedStatusId:$assignedStatus->id(),
                            dteDocumentId: (int) $saved->id()
                        );
                if (!$assigned) {
                    throw DteReservedFolioException::assignmentFailed($resolvedFolio->folioNumber);
                }
                /*
                * Marca el mayor folio consumido en el rango,
                * sin retroceder current_folio.
                */
                $this->folioReservationRepository->advanceCurrentFolio(
                        reservationId:$resolvedFolio->folioReservationId,
                        folio:$resolvedFolio->folioNumber
                    );
                /*
                * Historial folio:
                * reserved → assigned
                */
                $this->folioDetailEventRepository->create(
                    folioDetailId: $resolvedFolio->folioDetailId,
                    companyId: $input->companyId,
                    externalSystemId: $resolvedFolio->externalSystemId,
                    branchOfficeNumber: $resolvedFolio->branchOfficeNumber,
                    facilityNumber: $resolvedFolio->facilityNumber,
                    eventCode: 'assigned_to_dte',
                    fromStatusCode: 'reserved',
                    toStatusCode: 'assigned',
                    message: 'Folio asociado a documento DTE.',
                    userId: null,
                    payloadJson:json_encode([
                            'dte_document_id' => $saved->id(),
                            'external_id' => $saved->externalId(),
                            'dte_type' => $saved->dteType()->value,
                            'folio' => $resolvedFolio->folioNumber,
                            'caf_id' => $resolvedFolio->cafId,
                            'folio_reservation_id' => $resolvedFolio->folioReservationId,
                        ], JSON_UNESCAPED_UNICODE)
                );
                $this->recalculateCafCountersService->execute($resolvedFolio->cafId);
            }
            $this->logRepository->info
            (
                channel: 'dte_document',
                message: 'Documento DTE registrado correctamente.',
                context: [
                    'document_id' => $saved->id(),
                    'external_id' => $saved->externalId(),
                    'company_id' => $saved->companyId(),
                    'external_system_id' => $saved->externalSystemId(),
                    'dte_type' => $saved->dteType()->value,
                    'folio' => $saved->folio(),
                    'caf_id' => $saved->cafId(),
                    'folio_reservation_id' => $saved->folioReservationId(),
                    'branch_office_number' => $saved->branchOfficeNumber(),
                    'facility_number' => $saved->facilityNumber(),
                    'receiver_city_id' =>   $saved->receiver()->cityId(),
                ],
                companyId: $saved->companyId(),
                documentId: $saved->id(),
                code: 'DTE_DOCUMENT_CREATED'
            );
            return new CreateDteDocumentResultDto
            (
                id: (int) $saved->id(),
                externalId: $saved->externalId(),
                status: $saved->status(),
                dteType: $saved->dteType()->value,
                issueDate: $saved->issueDate(),
                receiverCityId: $saved->receiver()->cityId(),
                netAmount: $saved->netAmount(),
                exemptAmount: $saved->exemptAmount(),
                taxAmount: $saved->taxAmount(),
                totalAmount: $saved->totalAmount(),
                externalSystemId: $saved->externalSystemId(),
                folio: $saved->folio(),
                cafId: $saved->cafId(),
                folioReservationId: $saved->folioReservationId(),
                branchOfficeNumber: $saved->branchOfficeNumber(),
                facilityNumber: $saved->facilityNumber(),
                externalBranchCode: $saved->externalBranchCode(),
            );
        });
    }
}

