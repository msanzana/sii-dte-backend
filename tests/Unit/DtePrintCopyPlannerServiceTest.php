<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Entities\DtePrintConfiguration;
use App\Modules\Dte\Domain\Entities\DtePrintCopyConfiguration;
use App\Modules\Dte\Domain\Enums\PrintCopyType;
use App\Modules\Dte\Domain\Enums\PrintFormat;
use App\Modules\Dte\Domain\Services\DtePrintCopyPlannerService;
use App\Modules\Dte\Domain\ValueObjects\DtePrintPlanningContext;
use DomainException;
use Tests\TestCase;

final class DtePrintCopyPlannerServiceTest extends TestCase
{
    private DtePrintCopyPlannerService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service =
            new DtePrintCopyPlannerService();
    }

    public function test_factura_genera_original_y_cedible_cuando_corresponde(): void
    {
        $configuration =
            $this->facturaConfiguration();

        $plan = $this->service->plan(
            $configuration,
            new DtePrintPlanningContext(
                includeClientCopy: false,
            )
        );

        $this->assertSame(
            33,
            $plan->dteType()
        );

        $this->assertSame(
            PrintFormat::LETTER,
            $plan->format()
        );

        $this->assertCount(
            2,
            $plan->items()
        );

        $this->assertTrue(
            $plan->contains(
                PrintCopyType::ORIGINAL
            )
        );

        $this->assertTrue(
            $plan->contains(
                PrintCopyType::CEDIBLE
            )
        );

        $this->assertFalse(
            $plan->contains(
                PrintCopyType::CLIENT
            )
        );

        $this->assertSame(
            2,
            $plan->totalPhysicalCopies()
        );
    }

    public function test_rechaza_cedible_activo_configurado_como_opcional(): void
    {
        $configuration =
            new DtePrintConfiguration(
                dteDocumentTypeId: 1,
                dteType: 33,
                name: 'Factura Electrónica',
                isActive: true,
                isPrintable: true,
                defaultFormat: PrintFormat::LETTER,
                printParameters: null,
                copies: [
                    $this->copy(
                        PrintCopyType::ORIGINAL,
                        required: true,
                        order: 10,
                    ),
                    $this->copy(
                        PrintCopyType::CEDIBLE,
                        required: false,
                        order: 20,
                        legend: 'CEDIBLE',
                    ),
                ],
            );

        $this->expectException(
            DomainException::class
        );

        $this->service->plan(
            $configuration,
            new DtePrintPlanningContext()
        );
    }
    public function test_copia_cliente_solo_se_incluye_si_es_solicitada(): void
    {
        $plan = $this->service->plan(
            $this->facturaConfiguration(),
            new DtePrintPlanningContext(
                includeClientCopy: true,
            )
        );

        $this->assertTrue(
            $plan->contains(
                PrintCopyType::CLIENT
            )
        );
        $this->assertTrue(
            $plan->contains(
                PrintCopyType::CEDIBLE
            )
        );
        $this->assertSame(
            3,
            $plan->totalPhysicalCopies()
        );
    }

    public function test_boleta_no_inventa_cedible_aunque_el_contexto_lo_solicite(): void
    {
        $configuration =
            new DtePrintConfiguration(
                dteDocumentTypeId: 3,
                dteType: 39,
                name: 'Boleta Electrónica',
                isActive: true,
                isPrintable: true,
                defaultFormat: PrintFormat::TICKET_80,
                printParameters: null,
                copies: [
                    $this->copy(
                        PrintCopyType::ORIGINAL,
                        required: true,
                        order: 10,
                    ),
                ],
            );

        $plan = $this->service->plan(
            $configuration,
            new DtePrintPlanningContext(
                includeClientCopy: true,
            )
        );

        $this->assertCount(
            1,
            $plan->items()
        );

        $this->assertTrue(
            $plan->contains(
                PrintCopyType::ORIGINAL
            )
        );

        $this->assertFalse(
            $plan->contains(
                PrintCopyType::CEDIBLE
            )
        );
    }

    public function test_conserva_cantidad_y_leyenda_de_la_configuracion(): void
    {
        $configuration =
            new DtePrintConfiguration(
                dteDocumentTypeId: 6,
                dteType: 52,
                name: 'Guía de Despacho Electrónica',
                isActive: true,
                isPrintable: true,
                defaultFormat: PrintFormat::LETTER,
                printParameters: null,
                copies: [
                    $this->copy(
                        PrintCopyType::ORIGINAL,
                        required: true,
                        order: 10,
                        quantity: 2,
                    ),
                    $this->copy(
                        PrintCopyType::CEDIBLE,
                        required: true,
                        order: 20,
                        quantity: 1,
                        legend: 'CEDIBLE CON SU FACTURA',
                    ),
                ],
            );

        $plan = $this->service->plan(
            $configuration,
            new DtePrintPlanningContext()
        );

        $this->assertSame(
            3,
            $plan->totalPhysicalCopies()
        );

        $this->assertSame(
            'CEDIBLE CON SU FACTURA',
            $plan->items()[1]->legend()
        );
    }

    public function test_rechaza_tipo_dte_no_imprimible(): void
    {
        $configuration =
            new DtePrintConfiguration(
                dteDocumentTypeId: 1,
                dteType: 33,
                name: 'Factura Electrónica',
                isActive: true,
                isPrintable: false,
                defaultFormat: PrintFormat::LETTER,
                printParameters: null,
                copies: [
                    $this->copy(
                        PrintCopyType::ORIGINAL,
                        required: true,
                        order: 10,
                    ),
                ],
            );

        $this->expectException(
            DomainException::class
        );

        $this->service->plan(
            $configuration,
            new DtePrintPlanningContext()
        );
    }

    public function test_rechaza_configuracion_imprimible_sin_original(): void
    {
        $configuration =
            new DtePrintConfiguration(
                dteDocumentTypeId: 1,
                dteType: 33,
                name: 'Factura Electrónica',
                isActive: true,
                isPrintable: true,
                defaultFormat: PrintFormat::LETTER,
                printParameters: null,
                copies: [
                    $this->copy(
                        PrintCopyType::CLIENT,
                        required: false,
                        order: 10,
                    ),
                ],
            );

        $this->expectException(
            DomainException::class
        );

        $this->service->plan(
            $configuration,
            new DtePrintPlanningContext(
                includeClientCopy: true,
            )
        );
    }

    private function facturaConfiguration(): DtePrintConfiguration
    {
        return new DtePrintConfiguration(
            dteDocumentTypeId: 1,
            dteType: 33,
            name: 'Factura Electrónica',
            isActive: true,
            isPrintable: true,
            defaultFormat: PrintFormat::LETTER,
            printParameters: null,
            copies: [
                $this->copy(
                    PrintCopyType::ORIGINAL,
                    required: true,
                    order: 10,
                ),
                $this->copy(
                    PrintCopyType::CLIENT,
                    required: false,
                    order: 20,
                    legend: 'COPIA CLIENTE',
                ),
                $this->copy(
                    PrintCopyType::CEDIBLE,
                    required: true,
                    order: 30,
                    legend: 'CEDIBLE',
                ),
            ],
        );
    }

    private function copy(
        PrintCopyType $type,
        bool $required,
        int $order,
        int $quantity = 1,
        ?string $legend = null
    ): DtePrintCopyConfiguration {
        return new DtePrintCopyConfiguration(
            id: null,
            type: $type,
            name: $type->label(),
            isRequired: $required,
            defaultQuantity: $quantity,
            isActive: true,
            sortOrder: $order,
            legend: $legend,
            parameters: null,
        );
    }
}