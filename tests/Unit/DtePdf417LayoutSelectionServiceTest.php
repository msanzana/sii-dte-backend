<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\RendererContracts\DtePdf417RendererInterface;
use App\Modules\Dte\Domain\Services\DtePdf417LayoutSelectionService;
use App\Modules\Dte\Domain\Services\DtePdf417PhysicalMetricsService;
use App\Modules\Dte\Infrastructure\Barcode\LePdf417Renderer;
use Tests\TestCase;

final class DtePdf417LayoutSelectionServiceTest extends TestCase
{
    public function test_80_mm_conserva_preferencia_de_15_columnas(): void
    {
        $selection =
            $this->service()
                ->select(
                    $this->ted752Bytes(),
                    80.0
                );

        $this->assertSame(
            15,
            $selection->selectedColumns()
        );

        $this->assertFalse(
            $selection->columnsWereReduced()
        );

        $this->assertSame(
            47,
            $selection
                ->renderResult()
                ->rows()
        );

        $this->assertTrue(
            $selection
                ->physicalMetrics()
                ->fitsWithinWidthMm(
                    80.0
                )
        );
    }

    public function test_58_mm_reduce_automaticamente_a_11_columnas(): void
    {
        $selection =
            $this->service()
                ->select(
                    $this->ted752Bytes(),
                    58.0
                );

        $this->assertSame(
            11,
            $selection->selectedColumns()
        );

        $this->assertTrue(
            $selection->columnsWereReduced()
        );

        $this->assertSame(
            63,
            $selection
                ->renderResult()
                ->rows()
        );

        $this->assertTrue(
            $selection
                ->physicalMetrics()
                ->fitsWithinWidthMm(
                    58.0
                )
        );

        /*
         * Este caso cabe físicamente en 58 mm, pero el símbolo supera
         * ligeramente la recomendación de altura del SII.
         *
         * No lo convertimos en fallo técnico porque esa recomendación
         * no debe confundirse con los límites estructurales del PDF417.
         */

        $this->assertTrue(
            $selection
                ->exceedsRecommendedSymbolSize()
        );
    }

    public function test_respeta_preferencia_menor_si_cabe(): void
    {
        $selection =
            $this->service()
                ->select(
                    $this->tedCorto(),
                    80.0,
                    8
                );

        $this->assertSame(
            8,
            $selection->selectedColumns()
        );

        $this->assertFalse(
            $selection->columnsWereReduced()
        );
    }

    public function test_rechaza_ancho_donde_no_cabe_ni_una_columna(): void
    {
        $this->expectException(
            \DomainException::class
        );

        $this->expectExceptionMessage(
            'no permite alojar un PDF417'
        );

        $this->service()
            ->select(
                $this->tedCorto(),
                27.0
            );
    }

    public function test_rechaza_preferencia_de_columnas_invalida(): void
    {
        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->service()
            ->select(
                $this->tedCorto(),
                80.0,
                31
            );
    }

    private function service(): DtePdf417LayoutSelectionService
    {
        return new DtePdf417LayoutSelectionService(
            renderer:
                new LePdf417Renderer(),
            physicalMetricsService:
                new DtePdf417PhysicalMetricsService(),
        );
    }

    private function ted752Bytes(): string
    {
        return
            '<TED>'
            . str_repeat(
                'A',
                741
            )
            . '</TED>';
    }

    private function tedCorto(): string
    {
        return
            '<TED version="1.0">'
            . '<DD>'
            . '<RE>77760724-3</RE>'
            . '<TD>33</TD>'
            . '<F>35</F>'
            . '</DD>'
            . '</TED>';
    }
}