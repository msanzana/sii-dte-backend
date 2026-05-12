<?php
namespace App\Modules\Dte\Application\UseCases\Document;

use App\Modules\Dte\Application\DTOs\CreateDteDocumentInputDto;
use App\Modules\Dte\Application\DTOs\CreateDteDocumentResultDto;
use App\Modules\Dte\Application\Services\DteDocumentApplicationService;
use App\Modules\Dte\Domain\Exceptions\CityNotFoundException;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\Exceptions\ServicePausedException;
use App\Modules\Dte\Domain\RepositoryContracts\CityRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
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
    )
    {}
    public function execute(CreateDteDocumentInputDto $input): CreateDteDocumentResultDto
    {
        $paused = (bool)$this->settingRepository->get(
            config('dte.service_state.paused_key'),
            false
        );
        $reasonData = $this->settingRepository->get(
            config('dte.service_state.pause_reason_key'),
            null
        );
        if($paused){
            $reason = is_array($reasonData)
            ? ($reasonData['reason'] ?? 'Sin razón registrada')
            : 'Sin razón registrada';

            throw ServicePausedException::because($reason);
        }
        if(!$this->companyRepository->existActiveById($input->companyId)){
            throw CompanyNotFoundException::withId($input->companyId);
        }
        if($input->receiver->cityId !== null && !$this->cityRepository->existsActiveById($input->receiver->cityId)){
            throw CityNotFoundException::withId($input->receiver->cityId);
        }
        return DB::transaction(function() use ($input) {
            $domainDocument = $this->documentApplicationService->buildDomainDocument($input);
            $this->validationDomainService->validateForCreation($domainDocument);
            $saved = $this->documentRepository->create($domainDocument);
            $this->logRepository->info(
                channel: 'dte_document',
                message: 'Documento DTE Registrado correctamente.',
                context:[
                    'document_id' => $saved->id(),
                    'external_id' => $saved->externalId(),
                    'dte_type' => $saved->dteType()->value,
                    'receiver_city_id'  => $saved->receiver()->cityId(),
                ],
                companyId: $saved->companyId(),
                documentId: $saved->id(),
                code: 'DTE_DOCUMENT_CREATED'
            );
            return new CreateDteDocumentResultDto(
                id: $saved->id(),
                externalId: $saved->externalId(),
                status: $saved->status(),
                dteType: $saved->dteType()->value,
                issueDate: $saved->issueDate(),
                receiverCityId: $saved->receiver()->cityId(),
                netAmount: $saved->netAmount(),
                exemptAmount: $saved->exemptAmount(),
                taxAmount: $saved->taxAmount(),
                totalAmount: $saved->totalAmount()
            );
        });
    }
}

