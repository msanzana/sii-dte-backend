<?php

namespace App\Modules\Dte\Infrastructure\Pdf;

use App\Modules\Dte\Domain\Enums\PrintFormat;
use App\Modules\Dte\Domain\RendererContracts\DtePdfDocumentRendererInterface;
use App\Modules\Dte\Domain\ValueObjects\DtePdfDocumentRenderResult;
use App\Modules\Dte\Domain\ValueObjects\DtePrintablePackage;

final class DtePdfDocumentRendererRouter implements DtePdfDocumentRendererInterface
{
    public function __construct(
        private readonly DompdfDteLetterRenderer $letterRenderer,
        private readonly DompdfDteTicket80Renderer $ticket80Renderer,
        private readonly DompdfDteTicket58Renderer $ticket58Renderer,
    ) {}

    public function render(
        DtePrintablePackage $package
    ): DtePdfDocumentRenderResult {
        return match ($package->format()) {
            PrintFormat::LETTER =>
                $this->letterRenderer
                    ->render($package),

            PrintFormat::TICKET_80 =>
                $this->ticket80Renderer
                    ->render($package),

            PrintFormat::TICKET_58 =>
                $this->ticket58Renderer
                    ->render($package),
                };
    }
}