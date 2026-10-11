<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\RendererContracts\DtePdf417RendererInterface;
use App\Modules\Dte\Infrastructure\Barcode\LePdf417Renderer;
use Tests\TestCase;

final class DtePdf417RendererBindingTest extends TestCase
{
    public function test_contenedor_resuelve_renderer_pdf417(): void
    {
        $renderer =
            app(
                DtePdf417RendererInterface::class
            );

        $this->assertInstanceOf(
            LePdf417Renderer::class,
            $renderer
        );
    }
}