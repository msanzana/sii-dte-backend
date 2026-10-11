<?php

namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\RendererContracts\DtePdfDocumentRendererInterface;
use App\Modules\Dte\Domain\ValueObjects\DtePdfDocumentRenderResult;

final class RenderDtePrintablePdfService
{
    public function __construct(
        private readonly PrepareDtePrintablePackageService $preparePrintablePackageService,
        private readonly DtePdfDocumentRendererInterface $pdfRenderer,
    ) {}

    public function render(
        int $documentId,
        bool $includeClientCopy = false,
    ): DtePdfDocumentRenderResult {
        $package =
            $this->preparePrintablePackageService
                ->prepare(
                    documentId:
                        $documentId,
                    includeClientCopy:
                        $includeClientCopy,
                );

        return $this->pdfRenderer
            ->render(
                $package
            );
    }
}