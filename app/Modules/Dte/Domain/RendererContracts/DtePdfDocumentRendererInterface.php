<?php

namespace App\Modules\Dte\Domain\RendererContracts;

use App\Modules\Dte\Domain\ValueObjects\DtePdfDocumentRenderResult;
use App\Modules\Dte\Domain\ValueObjects\DtePrintablePackage;

interface DtePdfDocumentRendererInterface
{
    public function render(
        DtePrintablePackage $package
    ): DtePdfDocumentRenderResult;
}