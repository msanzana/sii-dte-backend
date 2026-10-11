<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Enums\PrintCopyType;
use App\Modules\Dte\Domain\Enums\PrintFormat;
use App\Modules\Dte\Domain\ValueObjects\DtePdf417LayoutSelection;
use App\Modules\Dte\Domain\ValueObjects\DtePdf417PhysicalMetrics;
use App\Modules\Dte\Domain\ValueObjects\DtePdf417RenderResult;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableCopy;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableDocument;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableIssuer;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableLineItem;
use App\Modules\Dte\Domain\ValueObjects\DtePrintablePackage;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableReceiver;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableTotals;
use App\Modules\Dte\Infrastructure\Pdf\DompdfDteTicket58Renderer;
use Tests\TestCase;

final class DompdfDteTicket58RendererTest extends TestCase
{
    public function test_ticket_58_mantiene_cada_copia_fisica_en_una_pagina(): void
    {
        $renderer =
            new DompdfDteTicket58Renderer();

        $result =
            $renderer->render(
                $this->package(
                    PrintFormat::TICKET_58
                )
            );

        $this->assertSame(
            'application/pdf',
            $result->mimeType()
        );

        $this->assertSame(
            PrintFormat::TICKET_58,
            $result->format()
        );

        $this->assertStringStartsWith(
            '%PDF-',
            $result->pdfBytes()
        );

        $this->assertSame(
            2,
            $result->pageCount()
        );

        $this->assertGreaterThan(
            1000,
            $result->byteLength()
        );

        $this->assertSame(
            64,
            strlen(
                $result->sha256()
            )
        );
    }

    public function test_ticket_58_usa_pdf417_reducido_a_11_columnas(): void
    {
        $package =
            $this->package(
                PrintFormat::TICKET_58
            );

        $this->assertSame(
            11,
            $package
                ->pdf417()
                ->selectedColumns()
        );

        $this->assertSame(
            15,
            $package
                ->pdf417()
                ->preferredColumns()
        );

        $this->assertTrue(
            $package
                ->pdf417()
                ->columnsWereReduced()
        );

        $this->assertLessThanOrEqual(
            50.0,
            $package
                ->pdf417()
                ->physicalMetrics()
                ->totalWidthMm()
        );
    }

    public function test_rechaza_formato_distinto_de_ticket_58(): void
    {
        $renderer =
            new DompdfDteTicket58Renderer();

        $this->expectException(
            \DomainException::class
        );

        $this->expectExceptionMessage(
            'solo admite formato Ticket 58'
        );

        $renderer->render(
            $this->package(
                PrintFormat::LETTER
            )
        );
    }

    private function package(
        PrintFormat $format
    ): DtePrintablePackage {
        $document =
            new DtePrintableDocument(
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
                            'PJE. LOS ARRAYANES 087 MZH ST25 DEPTO. #Port COMUNA CUNCO',
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
                            'Cliente prueba',
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
                            'A001',
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

                    new DtePrintableLineItem(
                        lineNumber:
                            2,
                        itemCodeType:
                            null,
                        itemCode:
                            'B001',
                        name:
                            'Producto B',
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

        /*
         * SVG técnico de prueba representando una geometría
         * reducida para Ticket 58.
         */
        $svg =
            '<svg xmlns="http://www.w3.org/2000/svg"'
            . ' width="256"'
            . ' height="177"'
            . ' viewBox="0 0 256 177">'
            . '<rect width="256" height="177" fill="white"/>'
            . '<rect x="0" y="0" width="8" height="177" fill="black"/>'
            . '<rect x="15" y="0" width="4" height="177" fill="black"/>'
            . '</svg>';

        $renderResult =
            new DtePdf417RenderResult(
                svg:
                    $svg,
                mimeType:
                    'image/svg+xml',
                columns:
                    11,
                rows:
                    59,
                securityLevel:
                    5,
                compactionCodeword:
                    901,
                moduleAspectRatio:
                    3,
                payloadByteLength:
                    752,
                payloadSha256:
                    hash(
                        'sha256',
                        str_repeat(
                            'A',
                            752
                        )
                    ),
            );

        /*
         * 256 módulos × 0.17018 mm = 43.56608 mm
         * + 2 Quiet Zones de 3.0 mm técnicos para este fixture
         * = 49.56608 mm.
         *
         * El cálculo productivo real sigue siendo responsabilidad
         * del servicio de métricas/layout.
         */
        $physicalMetrics =
            new DtePdf417PhysicalMetrics(
                xDimMils:
                    6.7,
                xDimMm:
                    0.17018,
                rowHeightMm:
                    0.51054,
                quietZoneMm:
                    3.0,
                symbolWidthModules:
                    256,
                symbolHeightModules:
                    177,
                symbolWidthMm:
                    43.56608,
                symbolHeightMm:
                    30.123,
                totalWidthMm:
                    49.56608,
                totalHeightMm:
                    36.123,
                withinRecommendedSymbolSize:
                    false,
            );

        $layout =
            new DtePdf417LayoutSelection(
                renderResult:
                    $renderResult,
                physicalMetrics:
                    $physicalMetrics,
                availableWidthMm:
                    50.0,
                preferredColumns:
                    15,
            );

        return new DtePrintablePackage(
            document:
                $document,
            format:
                $format,
            pdf417:
                $layout,
            copies: [
                new DtePrintableCopy(
                    type:
                        PrintCopyType::ORIGINAL,
                    name:
                        'Original',
                    quantity:
                        1,
                    legend:
                        null,
                ),

                new DtePrintableCopy(
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
        );
    }
}