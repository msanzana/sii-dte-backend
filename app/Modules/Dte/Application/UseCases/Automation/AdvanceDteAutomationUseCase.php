<?php
namespace App\Modules\Dte\Application\UseCases\Automation;

use App\Modules\Dte\Application\DTOs\AdvancedDteAutomationResultDto;
use App\Modules\Dte\Application\DTOs\AdvanceDteAutomationInputDto;
use App\Modules\Dte\Application\DTOs\BuildDteXmlInputDto;
use App\Modules\Dte\Application\DTOs\BuildTedInputDto;
use App\Modules\Dte\Application\DTOs\PrepareDteDocumentForXmlInputDto;
use App\Modules\Dte\Application\DTOs\SendSignedBoletaToSiiInputDto;
use App\Modules\Dte\Application\DTOs\SendSignedDteToSiiInputDto;
use App\Modules\Dte\Application\DTOs\SignDteXmlInputDto;
use App\Modules\Dte\Application\UseCases\Dispatch\SendSignedBoletaToSiiUseCase;
use App\Modules\Dte\Application\UseCases\Dispatch\SendSignedDteToSiiUseCase;
use App\Modules\Dte\Application\UseCases\Document\BuildDteXmlUseCase;
use App\Modules\Dte\Application\UseCases\Document\BuildTedUseCase;
use App\Modules\Dte\Application\UseCases\Document\PrepareDteDocumentForXmlUseCase;
use App\Modules\Dte\Application\UseCases\Document\SignDteXmlUseCase;
use App\Modules\Dte\Domain\Exceptions\DocumentNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\Services\DteAutomationPlannerService;

final class AdvanceDteAutomationUseCase
{
    public function __construct(
        private readonly DteDocumentRepositoryInterface $documentRepository,
        private readonly DteAutomationPlannerService $automationPlannerService,
        private readonly PrepareDteDocumentForXmlUseCase $prepareDteDocumentForXmlUseCase,
        private readonly BuildDteXmlUseCase $buildDteXmlUseCase,
        private readonly BuildTedUseCase $buildTedUseCase,
        private readonly SignDteXmlUseCase $signDteXmlUseCase,
        private readonly SendSignedDteToSiiUseCase $sendSignedDteToSiiUseCase,
        private readonly SendSignedBoletaToSiiUseCase $sendSignedBoletaToSiiUseCase,
    )
    {}

    public function execute(
        AdvanceDteAutomationInputDto $input
    ): AdvancedDteAutomationResultDto
    {
        $document = $this->documentRepository->findById($input->documentId);

        if(!$document)
        {
            throw DocumentNotFoundException::withId($input->documentId);
        }

        $previousStatus = $document->status();
        $action = $this->automationPlannerService->resolveNextAction($document);

        if($action === null)
        {
            return new AdvancedDteAutomationResultDto(
                documentId: $input->documentId,
                previousStatus: $previousStatus,
                currentStatus: $previousStatus,
                executeAction: null,
                shouldRequeueImmediately:false
            );
        }

        match($action)
        {
            'prepare_for_xml' => $this->prepareDteDocumentForXmlUseCase->execute(
                new PrepareDteDocumentForXmlInputDto(
                    documentId: $input->documentId
                )
            ),
            'build_xml' => $this->buildDteXmlUseCase->execute(
                new BuildDteXmlInputDto(
                    documentId: $input->documentId
                )
            ),
            'build_ted' => $this->buildTedUseCase->execute(
                new BuildTedInputDto(
                    documentId: $input->documentId
                )
            ),
                        'sign_xml' => $this->signDteXmlUseCase->execute(
                new SignDteXmlInputDto(
                    documentId: $input->documentId
                )
            ),
            'send_factura' => $this->sendSignedDteToSiiUseCase->execute(
                new SendSignedDteToSiiInputDto(
                    documentId: $input->documentId
                )
            ),
            'send_boleta' => $this->sendSignedBoletaToSiiUseCase->execute(
                new SendSignedBoletaToSiiInputDto(
                    documentId: $input->documentId
                )
            ),
            default => null,

        };

        $updatedDocument = $this->documentRepository->findById($input->documentId);

        if(!$updatedDocument)
        {
            throw DocumentNotFoundException::withId($input->documentId);
        }

        return new AdvancedDteAutomationResultDto(
            documentId: $updatedDocument->id(),
            previousStatus: $previousStatus,
            currentStatus: $updatedDocument->status(),
            executeAction: $action,
            shouldRequeueImmediately:$this->automationPlannerService->shouldRequeueImmediately(
                action: $action,
                currentStatus: $updatedDocument->status()
            ),
        );
    }
}
