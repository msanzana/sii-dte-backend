<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\Services\BuildDtePrintablePackageService;
use App\Modules\Dte\Domain\Enums\PrintCopyType;
use App\Modules\Dte\Domain\Enums\PrintFormat;
use App\Modules\Dte\Domain\Services\DtePdf417LayoutSelectionService;
use App\Modules\Dte\Domain\Services\DtePdf417PhysicalMetricsService;
use App\Modules\Dte\Domain\Services\DtePdf417PrintFormatSelectionService;
use App\Modules\Dte\Domain\Services\DtePrintFormatPhysicalProfileService;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableDocument;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableIssuer;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableLineItem;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableReceiver;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableTotals;
use App\Modules\Dte\Domain\ValueObjects\DtePrintPlan;
use App\Modules\Dte\Domain\ValueObjects\DtePrintPlanItem;
use App\Modules\Dte\Infrastructure\Barcode\LePdf417Renderer;
use Tests\TestCase;

final class BuildDtePrintablePackageServiceTest extends TestCase
{
    public function test_construye_paquete_con_original_y_cedible(): void
    {
        $package =
            $this->service()->build(
                $this->document(),
                new DtePrintPlan(
                    dteType:
                        33,
                    format:
                        PrintFormat::LETTER,
                    items: [
                        new DtePrintPlanItem(
                            type:
                                PrintCopyType::ORIGINAL,
                            name:
                                'Original',
                            quantity:
                                1,
                        ),

                        new DtePrintPlanItem(
                            type:
                                PrintCopyType::CEDIBLE,
                            name:
                                'Cedible',
                            quantity:
                                1,
                            legend:
                                'CEDIBLE',
                        ),
                    ],
                ),
                $this->ted752Bytes()
            );

        $this->assertSame(
            51,
            $package->document()->documentId()
        );

        $this->assertSame(
            PrintFormat::LETTER,
            $package->format()
        );

        $this->assertCount(
            2,
            $package->copies()
        );

        $this->assertSame(
            2,
            $package->totalPhysicalCopies()
        );

        $this->assertSame(
            PrintCopyType::ORIGINAL,
            $package->copies()[0]->type()
        );

        $this->assertSame(
            PrintCopyType::CEDIBLE,
            $package->copies()[1]->type()
        );

        $this->assertSame(
            'CEDIBLE',
            $package->copies()[1]->legend()
        );

        $this->assertSame(
            15,
            $package->pdf417()->selectedColumns()
        );
    }

    public function test_preserva_cantidad_configurada_de_cada_copia(): void
    {
        $package =
            $this->service()->build(
                $this->document(),
                new DtePrintPlan(
                    dteType:
                        33,
                    format:
                        PrintFormat::LETTER,
                    items: [
                        new DtePrintPlanItem(
                            type:
                                PrintCopyType::ORIGINAL,
                            name:
                                'Original',
                            quantity:
                                1,
                        ),

                        new DtePrintPlanItem(
                            type:
                                PrintCopyType::CLIENT,
                            name:
                                'Cliente',
                            quantity:
                                2,
                        ),
                    ],
                ),
                $this->ted752Bytes()
            );

        $this->assertCount(
            2,
            $package->copies()
        );

        $this->assertSame(
            3,
            $package->totalPhysicalCopies()
        );

        $this->assertSame(
            2,
            $package->copies()[1]->quantity()
        );
    }

    public function test_rechaza_tipo_dte_distinto_al_plan(): void
    {
        $this->expectException(
            \DomainException::class
        );

        $this->expectExceptionMessage(
            'no coincide con el plan'
        );

        $this->service()->build(
            $this->document(),
            new DtePrintPlan(
                dteType:
                    39,
                format:
                    PrintFormat::LETTER,
                items: [
                    new DtePrintPlanItem(
                        type:
                            PrintCopyType::ORIGINAL,
                        name:
                            'Original',
                        quantity:
                            1,
                    ),
                ],
            ),
            $this->ted752Bytes()
        );
    }

    public function test_rechaza_ted_vacio(): void
    {
        $this->expectException(
            \DomainException::class
        );

        $this->expectExceptionMessage(
            'requiere el TED persistido'
        );

        $this->service()->build(
            $this->document(),
            new DtePrintPlan(
                dteType:
                    33,
                format:
                    PrintFormat::LETTER,
                items: [
                    new DtePrintPlanItem(
                        type:
                            PrintCopyType::ORIGINAL,
                        name:
                            'Original',
                        quantity:
                            1,
                    ),
                ],
            ),
            ''
        );
    }

    public function test_ticket_58_usa_geometria_reducida_del_pdf417(): void
    {
        $package =
            $this->service()->build(
                $this->document(),
                new DtePrintPlan(
                    dteType:
                        33,
                    format:
                        PrintFormat::TICKET_58,
                    items: [
                        new DtePrintPlanItem(
                            type:
                                PrintCopyType::ORIGINAL,
                            name:
                                'Original',
                            quantity:
                                1,
                        ),
                    ],
                ),
                $this->ted752Bytes()
            );

        $this->assertSame(
            PrintFormat::TICKET_58,
            $package->format()
        );

        $this->assertLessThan(
            15,
            $package->pdf417()->selectedColumns()
        );

        $this->assertTrue(
            $package
                ->pdf417()
                ->physicalMetrics()
                ->fitsWithinWidthMm(
                    50.0
                )
        );
    }

    private function service(): BuildDtePrintablePackageService
    {
        $metrics =
            new DtePdf417PhysicalMetricsService();

        $layout =
            new DtePdf417LayoutSelectionService(
                renderer:
                    new LePdf417Renderer(),
                physicalMetricsService:
                    $metrics,
            );

        $formatSelection =
            new DtePdf417PrintFormatSelectionService(
                profileService:
                    new DtePrintFormatPhysicalProfileService(),
                layoutSelectionService:
                    $layout,
            );

        return new BuildDtePrintablePackageService(
            pdf417SelectionService:
                $formatSelection,
        );
    }

    private function document(): DtePrintableDocument
    {
        return new DtePrintableDocument(
            documentId:
                51,
            externalId:
                'external-51',
            dteType:
                33,
            folio:
                35,
            issueDate:
                '2026-10-06',

            issuer:
                new DtePrintableIssuer(
                    rut:
                        '77760724-3',
                    legalName:
                        'COMERCIALIZADORA H&D SPA',
                    tradeName:
                        'HEBER MARKETING',
                    giro:
                        'COMERCIO PRODUCTOS',
                    activityCode:
                        '464904',
                    address:
                        'PJE. LOS ARRAYANES 087',
                    city:
                        'Cunco',
                    comune:
                        'Cunco',
                    email:
                        'empresa@example.cl',
                    resolutionNumber:
                        '0',
                    resolutionDate:
                        '2026-07-27',
                ),

            receiver:
                new DtePrintableReceiver(
                    document:
                        '11111111-1',
                    name:
                        'Cliente dominio',
                    giro:
                        'Servicios',
                    address:
                        'Av. Alemania 123',
                    city:
                        'Temuco',
                    comune:
                        'Temuco',
                    email:
                        'cliente@example.cl',
                ),

            totals:
                new DtePrintableTotals(
                    net:
                        40000.0,
                    exempt:
                        0.0,
                    tax:
                        7600.0,
                    total:
                        47600.0,
                ),

            items: [
                new DtePrintableLineItem(
                    lineNumber:
                        1,
                    itemCodeType:
                        null,
                    itemCode:
                        null,
                    name:
                        'Producto A',
                    description:
                        null,
                    quantity:
                        2.0,
                    unitPrice:
                        10000.0,
                    discountPercent:
                        0.0,
                    discountAmount:
                        0.0,
                    taxExempt:
                        false,
                    lineAmount:
                        20000.0,
                ),
            ],

            references:
                [],
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