<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Enums\PrintFormat;
use App\Modules\Dte\Domain\Services\DtePdf417LayoutSelectionService;
use App\Modules\Dte\Domain\Services\DtePdf417PhysicalMetricsService;
use App\Modules\Dte\Domain\Services\DtePdf417PrintFormatSelectionService;
use App\Modules\Dte\Domain\Services\DtePrintFormatPhysicalProfileService;
use App\Modules\Dte\Infrastructure\Barcode\LePdf417Renderer;
use Tests\TestCase;

final class DtePdf417PrintFormatSelectionServiceTest extends TestCase
{
    public function test_carta_conserva_15_columnas(): void
    {
        $selection =
            $this->service()->select(
                $this->ted752Bytes(),
                PrintFormat::LETTER
            );

        $this->assertSame(
            15,
            $selection->selectedColumns()
        );
    }

    public function test_ticket_80_conserva_15_columnas_con_72_mm_utiles(): void
    {
        $selection =
            $this->service()->select(
                $this->ted752Bytes(),
                PrintFormat::TICKET_80
            );

        $this->assertSame(
            15,
            $selection->selectedColumns()
        );

        $this->assertTrue(
            $selection
                ->physicalMetrics()
                ->fitsWithinWidthMm(
                    72.0
                )
        );
    }

    public function test_ticket_58_reduce_columnas_segun_50_mm_utiles(): void
    {
        $selection =
            $this->service()->select(
                $this->ted752Bytes(),
                PrintFormat::TICKET_58
            );

        $this->assertLessThan(
            15,
            $selection->selectedColumns()
        );

        $this->assertTrue(
            $selection->columnsWereReduced()
        );

        $this->assertTrue(
            $selection
                ->physicalMetrics()
                ->fitsWithinWidthMm(
                    50.0
                )
        );
    }

    private function service(): DtePdf417PrintFormatSelectionService
    {
        $renderer =
            new LePdf417Renderer();

        $metrics =
            new DtePdf417PhysicalMetricsService();

        $layout =
            new DtePdf417LayoutSelectionService(
                renderer: $renderer,
                physicalMetricsService:
                    $metrics,
            );

        return new DtePdf417PrintFormatSelectionService(
            profileService:
                new DtePrintFormatPhysicalProfileService(),
            layoutSelectionService:
                $layout,
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
}