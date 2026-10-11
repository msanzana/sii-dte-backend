<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\Services\BuildDtePrintableDocumentService;
use App\Modules\Dte\Application\Services\BuildDtePrintablePackageService;
use App\Modules\Dte\Application\Services\PrepareDtePrintablePackageService;
use App\Modules\Dte\Application\Services\ResolveDtePrintPlanService;
use App\Modules\Dte\Domain\Entities\Company;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Entities\DteLineItem;
use App\Modules\Dte\Domain\Entities\DtePrintConfiguration;
use App\Modules\Dte\Domain\Entities\DtePrintCopyConfiguration;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\Enums\PrintCopyType;
use App\Modules\Dte\Domain\Enums\PrintFormat;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DtePrintConfigurationRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\LocationRepositoryInterface;
use App\Modules\Dte\Domain\Services\DtePdf417LayoutSelectionService;
use App\Modules\Dte\Domain\Services\DtePdf417PhysicalMetricsService;
use App\Modules\Dte\Domain\Services\DtePdf417PrintFormatSelectionService;
use App\Modules\Dte\Domain\Services\DtePrintCopyPlannerService;
use App\Modules\Dte\Domain\Services\DtePrintFormatPhysicalProfileService;
use App\Modules\Dte\Domain\ValueObjects\LocationSummary;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use App\Modules\Dte\Infrastructure\Barcode\LePdf417Renderer;
use Tests\TestCase;

final class PrepareDtePrintablePackageServiceTest extends TestCase
{
    public function test_prepara_factura_con_original_y_cedible_sin_copia_cliente(): void
    {
        $package =
            $this->service()->prepare(
                documentId: 51,
                includeClientCopy: false,
            );

        $this->assertSame(
            51,
            $package->document()->documentId()
        );

        $this->assertSame(
            33,
            $package->document()->dteType()
        );

        $this->assertSame(
            35,
            $package->document()->folio()
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
            PrintCopyType::ORIGINAL,
            $package->copies()[0]->type()
        );

        $this->assertSame(
            PrintCopyType::CEDIBLE,
            $package->copies()[1]->type()
        );

        $this->assertSame(
            2,
            $package->totalPhysicalCopies()
        );

        $this->assertSame(
            15,
            $package->pdf417()->selectedColumns()
        );
    }

    public function test_agrega_copia_cliente_sin_alterar_identidad_fiscal(): void
    {
        $package =
            $this->service()->prepare(
                documentId: 51,
                includeClientCopy: true,
            );

        $this->assertCount(
            3,
            $package->copies()
        );

        $this->assertSame(
            PrintCopyType::ORIGINAL,
            $package->copies()[0]->type()
        );

        $this->assertSame(
            PrintCopyType::CLIENT,
            $package->copies()[1]->type()
        );

        $this->assertSame(
            PrintCopyType::CEDIBLE,
            $package->copies()[2]->type()
        );

        $this->assertSame(
            51,
            $package->document()->documentId()
        );

        $this->assertSame(
            35,
            $package->document()->folio()
        );

        $this->assertSame(
            3,
            $package->totalPhysicalCopies()
        );
    }

    public function test_rechaza_identificador_no_positivo(): void
    {
        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'debe ser mayor que cero'
        );

        $this->service()->prepare(
            documentId: 0
        );
    }

    public function test_rechaza_documento_inexistente(): void
    {
        $documentRepository =
            $this->createMock(
                DteDocumentRepositoryInterface::class
            );

        $documentRepository
            ->method('findById')
            ->willReturn(null);

        $service =
            $this->service(
                documentRepository:
                    $documentRepository
            );

        $this->expectException(
            \DomainException::class
        );

        $this->expectExceptionMessage(
            'No existe el DTE con id 999999999.'
        );

        $service->prepare(
            documentId: 999999999
        );
    }

    private function service(
        ?DteDocumentRepositoryInterface $documentRepository = null
    ): PrepareDtePrintablePackageService {
        if ($documentRepository === null) {
            $documentRepository =
                $this->createMock(
                    DteDocumentRepositoryInterface::class
                );

            $documentRepository
                ->method('findById')
                ->willReturn(
                    $this->document()
                );
        }

        $configurationRepository =
            $this->createMock(
                DtePrintConfigurationRepositoryInterface::class
            );

        $configurationRepository
            ->method('findByDteTypeCode')
            ->willReturn(
                $this->printConfiguration()
            );

        $companyRepository =
            $this->createMock(
                CompanyRepositoryInterface::class
            );

        $companyRepository
            ->method('findById')
            ->willReturn(
                $this->company()
            );

        $locationRepository =
            $this->createMock(
                LocationRepositoryInterface::class
            );

        $locationRepository
            ->method('findSummaryByCityId')
            ->willReturnCallback(
                static fn (int $cityId) =>
                    match ($cityId) {
                        213 =>
                            new LocationSummary(
                                cityId: 213,
                                cityName: 'Cunco',
                                comuneName: 'Cunco',
                            ),

                        211 =>
                            new LocationSummary(
                                cityId: 211,
                                cityName: 'Temuco',
                                comuneName: 'Temuco',
                            ),

                        default =>
                            null,
                    }
            );

        $resolvePrintPlanService =
            new ResolveDtePrintPlanService(
                configurationRepository:
                    $configurationRepository,
                planner:
                    new DtePrintCopyPlannerService(),
            );

        $buildPrintableDocumentService =
            new BuildDtePrintableDocumentService(
                companyRepository:
                    $companyRepository,
                locationRepository:
                    $locationRepository,
            );

        $physicalMetricsService =
            new DtePdf417PhysicalMetricsService();

        $layoutSelectionService =
            new DtePdf417LayoutSelectionService(
                renderer:
                    new LePdf417Renderer(),
                physicalMetricsService:
                    $physicalMetricsService,
            );

        $pdf417PrintFormatSelectionService =
            new DtePdf417PrintFormatSelectionService(
                profileService:
                    new DtePrintFormatPhysicalProfileService(),
                layoutSelectionService:
                    $layoutSelectionService,
            );

        $buildPrintablePackageService =
            new BuildDtePrintablePackageService(
                pdf417SelectionService:
                    $pdf417PrintFormatSelectionService,
            );

        return new PrepareDtePrintablePackageService(
            documentRepository:
                $documentRepository,
            resolvePrintPlanService:
                $resolvePrintPlanService,
            buildPrintableDocumentService:
                $buildPrintableDocumentService,
            buildPrintablePackageService:
                $buildPrintablePackageService,
        );
    }

    private function document(): DteDocument
    {
        return new DteDocument(
            id:
                51,

            externalId:
                'external-51',

            companyId:
                1,

            dteType:
                DteType::FACTURA_ELECTRTONICA,

            issueDate:
                '2026-10-06',

            status:
                'accepted',

            receiver:
                new ReceiverData(
                    document:
                        '11111111-1',
                    name:
                        'Cliente prueba',
                    giro:
                        'Servicios',
                    address:
                        'Av. Alemania 123',
                    cityId:
                        211,
                    email:
                        'cliente@example.cl',
                ),

            netAmount:
                40000.0,

            exemptAmount:
                0.0,

            taxAmount:
                7600.0,

            totalAmount:
                47600.0,

            items: [
                new DteLineItem(
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
                    extraPayload:
                        null,
                ),
            ],

            references:
                [],

            headerPayload:
                null,

            totalsPayload: [
                'net' => 40000.0,
                'exempt' => 0.0,
                'tax' => 7600.0,
                'total' => 47600.0,
            ],

            rawInput:
                null,

            folio:
                35,

            siiEnvironment:
                'cert',

            unsignedXmlPath:
                'unsigned/document.xml',

            signedXmlPath:
                'signed/document.xml',

            tedXml:
                $this->ted752Bytes(),
        );
    }

    private function company(): Company
    {
        return new Company(
            id:
                1,
            rut:
                '77760724-3',
            rutBody:
                '77760724',
            rutDv:
                '3',
            legalName:
                'COMERCIALIZADORA H&D SPA',
            tradeName:
                'HEBER MARKETING',
            giro:
                'COMERCIO PRODUCTOS',
            siiActivityCode:
                '464904',
            address:
                'PJE. LOS ARRAYANES 087',
            cityId:
                213,
            dteEmail:
                'empresa@example.cl',
            resolutionNumber:
                '0',
            resolutionDate:
                '2026-07-27',
            siiEnvironment:
                'cert',
            isActive:
                true,
        );
    }

    private function printConfiguration(): DtePrintConfiguration
    {
        return new DtePrintConfiguration(
            dteDocumentTypeId:
                1,

            dteType:
                33,

            name:
                'Factura Electrónica',

            isActive:
                true,

            isPrintable:
                true,

            defaultFormat:
                PrintFormat::LETTER,

            printParameters:
                null,

            copies: [
                new DtePrintCopyConfiguration(
                    id:
                        1,
                    type:
                        PrintCopyType::ORIGINAL,
                    name:
                        'Original',
                    isRequired:
                        true,
                    defaultQuantity:
                        1,
                    isActive:
                        true,
                    sortOrder:
                        1,
                    legend:
                        null,
                    parameters:
                        null,
                ),

                new DtePrintCopyConfiguration(
                    id:
                        2,
                    type:
                        PrintCopyType::CLIENT,
                    name:
                        'Copia Cliente',
                    isRequired:
                        false,
                    defaultQuantity:
                        1,
                    isActive:
                        true,
                    sortOrder:
                        2,
                    legend:
                        'COPIA CLIENTE',
                    parameters:
                        null,
                ),

                new DtePrintCopyConfiguration(
                    id:
                        3,
                    type:
                        PrintCopyType::CEDIBLE,
                    name:
                        'Cedible',
                    isRequired:
                        true,
                    defaultQuantity:
                        1,
                    isActive:
                        true,
                    sortOrder:
                        3,
                    legend:
                        'CEDIBLE',
                    parameters:
                        null,
                ),
            ],
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