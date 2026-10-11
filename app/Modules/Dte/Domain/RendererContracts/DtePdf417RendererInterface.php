<?php

namespace App\Modules\Dte\Domain\RendererContracts;

use App\Modules\Dte\Domain\ValueObjects\DtePdf417RenderResult;

interface DtePdf417RendererInterface
{
    public function render(
        string $tedXmlUtf8,
        int $columns = 15
    ): DtePdf417RenderResult;
}