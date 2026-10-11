<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\Services\ResolveDtePrintPlanService;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Entities\DtePrintConfiguration;
use App\Modules\Dte\Domain\Entities\DtePrintCopyConfiguration;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\Enums\PrintCopyType;
use App\Modules\Dte\Domain\Enums\PrintFormat;
use App\Modules\Dte\Domain\RepositoryContracts\DtePrintConfigurationRepositoryInterface;
use App\Modules\Dte\Domain\Services\DtePrintCopyPlannerService;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use DomainException;
use Tests\TestCase;

final class ResolveDtePrintPlanServiceTest extends TestCase
{
    public function test_resuelve_plan_desde_documento_real(): void
    {
        $service =
            $this->service(
                $this->configuration()
            );

        $plan = $service->resolve(
            $this->document()
        );

        $this->assertSame(
            33,
            $plan->dteType()
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

    public function test_incluye_copia_cliente_cuando_es_solicitada(): void
    {
        $plan = $this
            ->service(
                $this->configuration()
            )
            ->resolve(
                $this->document(),
                includeClientCopy: true
            );

        $this->assertTrue(
            $plan->contains(
                PrintCopyType::CLIENT
            )
        );

        $this->assertSame(
            3,
            $plan->totalPhysicalCopies()
        );
    }

    public function test_rechaza_tipo_sin_configuracion_de_impresion(): void
    {
        $service =
            $this->service(null);

        $this->expectException(
            DomainException::class
        );

        $service->resolve(
            $this->document()
        );
    }

    public function test_rechaza_documento_sin_folio(): void
    {
        $service =
            $this->service(
                $this->configuration()
            );

        $this->expectException(
            DomainException::class
        );

        $service->resolve(
            $this->document(
                folio: null
            )
        );
    }

    public function test_rechaza_documento_sin_ted_persistido(): void
    {
        $service =
            $this->service(
                $this->configuration()
            );

        $this->expectException(
            DomainException::class
        );

        $service->resolve(
            $this->document(
                tedXml: null
            )
        );
    }

    public function test_rechaza_documento_sin_xml_firmado(): void
    {
        $service =
            $this->service(
                $this->configuration()
            );

        $this->expectException(
            DomainException::class
        );

        $service->resolve(
            $this->document(
                signedXmlPath: null
            )
        );
    }

    private function service(
        ?DtePrintConfiguration $configuration
    ): ResolveDtePrintPlanService {
        $repository =
            new class(
                $configuration
            ) implements DtePrintConfigurationRepositoryInterface {
                public function __construct(
                    private readonly ?DtePrintConfiguration $configuration
                ) {}

                public function findByDteTypeCode(
                    int $dteType
                ): ?DtePrintConfiguration {
                    if (
                        $this->configuration === null
                    ) {
                        return null;
                    }

                    if (
                        $this->configuration
                            ->dteType()
                        !== $dteType
                    ) {
                        return null;
                    }

                    return $this->configuration;
                }
            };

        return new ResolveDtePrintPlanService(
            configurationRepository: $repository,
            planner:
                new DtePrintCopyPlannerService(),
        );
    }

    private function configuration(): DtePrintConfiguration
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
                new DtePrintCopyConfiguration(
                    id: 1,
                    type:
                        PrintCopyType::ORIGINAL,
                    name: 'Original',
                    isRequired: true,
                    defaultQuantity: 1,
                    isActive: true,
                    sortOrder: 10,
                    legend: null,
                    parameters: null,
                ),
                new DtePrintCopyConfiguration(
                    id: 2,
                    type:
                        PrintCopyType::CLIENT,
                    name: 'Copia Cliente',
                    isRequired: false,
                    defaultQuantity: 1,
                    isActive: true,
                    sortOrder: 20,
                    legend: 'COPIA CLIENTE',
                    parameters: null,
                ),
                new DtePrintCopyConfiguration(
                    id: 3,
                    type:
                        PrintCopyType::CEDIBLE,
                    name: 'Cedible',
                    isRequired: true,
                    defaultQuantity: 1,
                    isActive: true,
                    sortOrder: 30,
                    legend: 'CEDIBLE',
                    parameters: null,
                ),
            ],
        );
    }

    private function document(
        ?int $folio = 100,
        ?string $tedXml = '<TED>TEST</TED>',
        ?string $signedXmlPath =
            'companies/1/dte/33/100/signed.xml'
    ): DteDocument {
        return new DteDocument(
            id: 100,
            externalId:
                '00000000-0000-4000-8000-000000000100',
            companyId: 1,
            dteType:
                DteType::FACTURA_ELECTRTONICA,
            issueDate: '2026-10-09',
            status: 'signed',
            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Cliente prueba',
                giro: 'SERVICIOS',
                address: 'Dirección prueba',
                cityId: 1,
                email: null,
            ),
            netAmount: 1000,
            exemptAmount: 0,
            taxAmount: 190,
            totalAmount: 1190,
            items: [],
            references: [],
            headerPayload: [],
            totalsPayload: null,
            rawInput: null,
            folio: $folio,
            siiEnvironment: 'cert',
            unsignedXmlPath:
                'companies/1/dte/33/100/unsigned.xml',
            signedXmlPath: $signedXmlPath,
            tedXml: $tedXml,
        );
    }
}