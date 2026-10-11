<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\RendererContracts\DtePdfDocumentRendererInterface;
use App\Modules\Dte\Infrastructure\Pdf\DtePdfDocumentRendererRouter;
use Tests\TestCase;

final class DtePdfDocumentRendererBindingTest extends TestCase
{
    public function test_contenedor_resuelve_renderer_pdf_documental(): void
    {
        $renderer =
            $this->app->make(
                DtePdfDocumentRendererInterface::class
            );

        $this->assertInstanceOf(
            DtePdfDocumentRendererRouter::class,
            $renderer
        );
    }
}