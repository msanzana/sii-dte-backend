<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Services\DtePdf417PhysicalMetricsService;
use App\Modules\Dte\Domain\ValueObjects\DtePdf417RenderResult;
use Tests\TestCase;

final class DtePdf417PhysicalMetricsServiceTest extends TestCase
{
    public function test_calcula_dimensiones_del_pdf417_real_de_prueba(): void
    {
        $metrics =
            (new DtePdf417PhysicalMetricsService())
                ->calculate(
                    $this->pdf417Result()
                );

        $this->assertSame(
            324,
            $metrics->symbolWidthModules()
        );

        $this->assertSame(
            141,
            $metrics->symbolHeightModules()
        );

        $this->assertEqualsWithDelta(
            0.17018,
            $metrics->xDimMm(),
            0.000001
        );

        $this->assertEqualsWithDelta(
            0.51054,
            $metrics->rowHeightMm(),
            0.000001
        );

        $this->assertEqualsWithDelta(
            6.35,
            $metrics->quietZoneMm(),
            0.000001
        );

        $this->assertEqualsWithDelta(
            55.13832,
            $metrics->symbolWidthMm(),
            0.00001
        );

        $this->assertEqualsWithDelta(
            23.99538,
            $metrics->symbolHeightMm(),
            0.00001
        );

        $this->assertEqualsWithDelta(
            67.83832,
            $metrics->totalWidthMm(),
            0.00001
        );

        $this->assertEqualsWithDelta(
            36.69538,
            $metrics->totalHeightMm(),
            0.00001
        );

        $this->assertTrue(
            $metrics
                ->withinRecommendedSymbolSize()
        );
    }

    public function test_pdf417_actual_cabe_en_ticket_80_pero_no_en_ticket_58(): void
    {
        $metrics =
            (new DtePdf417PhysicalMetricsService())
                ->calculate(
                    $this->pdf417Result()
                );

        $this->assertTrue(
            $metrics->fitsWithinWidthMm(
                80.0
            )
        );

        $this->assertFalse(
            $metrics->fitsWithinWidthMm(
                58.0
            )
        );
    }

    public function test_rechaza_x_dim_inferior_al_minimo(): void
    {
        $this->expectException(
            \InvalidArgumentException::class
        );

        (new DtePdf417PhysicalMetricsService())
            ->calculate(
                $this->pdf417Result(),
                6.6
            );
    }

    public function test_detecta_simbolo_que_supera_altura_recomendada(): void
    {
        $metrics =
            (new DtePdf417PhysicalMetricsService())
                ->calculate(
                    $this->pdf417Result(
                        rows: 60
                    )
                );

        $this->assertFalse(
            $metrics
                ->withinRecommendedSymbolSize()
        );

        $this->assertGreaterThan(
            30.0,
            $metrics->symbolHeightMm()
        );
    }
    public function test_calcula_maximo_de_columnas_para_80_mm(): void
    {
        $service =
            new DtePdf417PhysicalMetricsService();

        $columns =
            $service->maximumColumnsForWidthMm(
                80.0
            );

        $this->assertSame(
            19,
            $columns
        );
    }

    public function test_calcula_maximo_de_columnas_para_58_mm(): void
    {
        $service =
            new DtePdf417PhysicalMetricsService();

        $columns =
            $service->maximumColumnsForWidthMm(
                58.0
            );

        $this->assertSame(
            11,
            $columns
        );
    }
    private function pdf417Result(
        int $rows = 47
    ): DtePdf417RenderResult {
        return new DtePdf417RenderResult(
            svg: '<svg/>',
            mimeType: 'image/svg+xml',
            columns: 15,
            rows: $rows,
            securityLevel: 5,
            compactionCodeword: 901,
            moduleAspectRatio: 3,
            payloadByteLength: 752,
            payloadSha256:
                str_repeat('a', 64),
        );
    }
}
