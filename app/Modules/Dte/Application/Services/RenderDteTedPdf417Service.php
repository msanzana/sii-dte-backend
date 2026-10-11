<?php

namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\RendererContracts\DtePdf417RendererInterface;
use App\Modules\Dte\Domain\ValueObjects\DtePdf417RenderResult;

final class RenderDteTedPdf417Service
{
    public function __construct(
        private readonly DtePdf417RendererInterface $renderer,
    ) {}

    public function render(
        DteDocument $document,
        int $columns = 15
    ): DtePdf417RenderResult {
        $tedXml =
            $document->tedXml();

        if (
            $tedXml === null
            || trim($tedXml) === ''
        ) {
            throw new \DomainException(
                'El DTE no tiene TED persistido y no puede generar su PDF417.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Muy importante
        |--------------------------------------------------------------------------
        |
        | Entregamos al renderer exactamente el ted_xml persistido.
        | Este servicio no lo reconstruye ni lo normaliza.
        |
        */

        return $this->renderer->render(
            $tedXml,
            $columns
        );
    }
}