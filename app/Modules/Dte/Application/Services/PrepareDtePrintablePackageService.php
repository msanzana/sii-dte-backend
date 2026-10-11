<?php

namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\ValueObjects\DtePrintablePackage;

final class PrepareDtePrintablePackageService
{
    public function __construct(
        private readonly DteDocumentRepositoryInterface $documentRepository,
        private readonly ResolveDtePrintPlanService $resolvePrintPlanService,
        private readonly BuildDtePrintableDocumentService $buildPrintableDocumentService,
        private readonly BuildDtePrintablePackageService $buildPrintablePackageService,
    ) {}

    public function prepare(
        int $documentId,
        bool $includeClientCopy = false,
    ): DtePrintablePackage {
        if ($documentId < 1) {
            throw new \InvalidArgumentException(
                'El identificador del DTE debe ser mayor que cero.'
            );
        }

        $document =
            $this->documentRepository
                ->findById(
                    $documentId
                );

        if ($document === null) {
            throw new \DomainException(
                "No existe el DTE con id {$documentId}."
            );
        }

        $plan =
            $this->resolvePrintPlanService
                ->resolve(
                    document:
                        $document,
                    includeClientCopy:
                        $includeClientCopy,
                );

        $printableDocument =
            $this->buildPrintableDocumentService
                ->build(
                    $document
                );

        /*
         * ResolveDtePrintPlanService ya garantiza que existe
         * un TED persistido y no vacío.
         *
         * Este guard protege el contrato interno ante una
         * modificación futura del resolver.
         */
        $tedXml =
            $document->tedXml();

        if ($tedXml === null) {
            throw new \LogicException(
                'El plan de impresión fue resuelto para un DTE sin TED persistido.'
            );
        }

        return $this->buildPrintablePackageService
            ->build(
                document:
                    $printableDocument,
                plan:
                    $plan,
                tedXmlUtf8:
                    $tedXml,
            );
    }
}