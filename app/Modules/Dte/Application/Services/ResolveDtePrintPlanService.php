<?php

namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\RepositoryContracts\DtePrintConfigurationRepositoryInterface;
use App\Modules\Dte\Domain\Services\DtePrintCopyPlannerService;
use App\Modules\Dte\Domain\ValueObjects\DtePrintPlan;
use App\Modules\Dte\Domain\ValueObjects\DtePrintPlanningContext;

final class ResolveDtePrintPlanService
{
    public function __construct(
        private readonly DtePrintConfigurationRepositoryInterface $configurationRepository,
        private readonly DtePrintCopyPlannerService $planner,
    ) {}

    public function resolve(
        DteDocument $document,
        bool $includeClientCopy = false
    ): DtePrintPlan {
        $configuration =
            $this->configurationRepository
                ->findByDteTypeCode(
                    $document->dteType()->value
                );

        if ($configuration === null) {
            throw new \DomainException(
                'No existe configuración de impresión para el tipo DTE.'
            );
        }

        $this->assertDocumentIsReady(
            $document
        );

        return $this->planner->plan(
            $configuration,
            new DtePrintPlanningContext(
                includeClientCopy:
                    $includeClientCopy,
            )
        );
    }

    private function assertDocumentIsReady(
        DteDocument $document
    ): void {
        if ($document->folio() === null) {
            throw new \DomainException(
                'El DTE no tiene folio asignado y no puede representarse para impresión.'
            );
        }

        $tedXml = $document->tedXml();

        if (
            $tedXml === null
            || trim($tedXml) === ''
        ) {
            throw new \DomainException(
                'El DTE no tiene TED persistido y no puede generar su representación impresa.'
            );
        }

        $signedXmlPath =
            $document->signedXmlPath();

        if (
            $signedXmlPath === null
            || trim($signedXmlPath) === ''
        ) {
            throw new \DomainException(
                'El DTE no tiene XML firmado y no puede generar su representación impresa.'
            );
        }
    }
}