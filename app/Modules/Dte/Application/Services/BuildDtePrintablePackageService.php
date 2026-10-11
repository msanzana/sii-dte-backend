<?php

namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\Services\DtePdf417PrintFormatSelectionService;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableCopy;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableDocument;
use App\Modules\Dte\Domain\ValueObjects\DtePrintablePackage;
use App\Modules\Dte\Domain\ValueObjects\DtePrintPlan;

final class BuildDtePrintablePackageService
{
    public function __construct(
        private readonly DtePdf417PrintFormatSelectionService $pdf417SelectionService,
    ) {}

    public function build(
        DtePrintableDocument $document,
        DtePrintPlan $plan,
        string $tedXmlUtf8,
    ): DtePrintablePackage {
        if ($tedXmlUtf8 === '') {
            throw new \DomainException(
                'El paquete imprimible requiere el TED persistido.'
            );
        }

        if ($document->dteType() !== $plan->dteType()) {
            throw new \DomainException(
                sprintf(
                    'El tipo DTE del documento imprimible (%d) no coincide con el plan de impresión (%d).',
                    $document->dteType(),
                    $plan->dteType()
                )
            );
        }

        $pdf417 =
            $this->pdf417SelectionService->select(
                tedXmlUtf8:
                    $tedXmlUtf8,
                format:
                    $plan->format(),
            );

        $copies =
            array_map(
                static fn ($item) =>
                    new DtePrintableCopy(
                        type:
                            $item->type(),
                        name:
                            $item->name(),
                        quantity:
                            $item->quantity(),
                        legend:
                            $item->legend(),
                    ),
                $plan->items()
            );

        return new DtePrintablePackage(
            document:
                $document,
            format:
                $plan->format(),
            pdf417:
                $pdf417,
            copies:
                $copies,
        );
    }
}