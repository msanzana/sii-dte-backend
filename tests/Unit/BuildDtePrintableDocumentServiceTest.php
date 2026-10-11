<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\Services\BuildDtePrintableDocumentService;
use App\Modules\Dte\Domain\Entities\Company;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Entities\DteLineItem;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\Exceptions\CityNotFoundException;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\LocationRepositoryInterface;
use App\Modules\Dte\Domain\ValueObjects\LocationSummary;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use Tests\TestCase;
use App\Modules\Dte\Domain\StorageContracts\CompanyLogoStorageInterface;
use App\Modules\Dte\Domain\ValueObjects\CompanyLogoFile;

final class BuildDtePrintableDocumentServiceTest extends TestCase
{
    public function test_construye_documento_imprimible_desde_dominio(): void
    {
        $service =
            $this->service();

        $printable =
            $service->build(
                $this->document()
            );

        $this->assertSame(
            51,
            $printable->documentId()
        );

        $this->assertSame(
            'external-51',
            $printable->externalId()
        );

        $this->assertSame(
            33,
            $printable->dteType()
        );

        $this->assertSame(
            35,
            $printable->folio()
        );

        $this->assertSame(
            '2026-10-06',
            $printable->issueDate()
        );

        $this->assertSame(
            'COMERCIALIZADORA H&D SPA',
            $printable->issuer()->legalName()
        );

        $this->assertSame(
            'Cunco',
            $printable->issuer()->city()
        );

        $this->assertSame(
            'Cliente dominio',
            $printable->receiver()->name()
        );

        $this->assertSame(
            'Temuco',
            $printable->receiver()->city()
        );

        $this->assertCount(
            1,
            $printable->items()
        );

        $this->assertSame(
            40000.0,
            $printable->totals()->net()
        );

        $this->assertSame(
            7600.0,
            $printable->totals()->tax()
        );

        $this->assertSame(
            47600.0,
            $printable->totals()->total()
        );
    }

    public function test_preserva_cantidad_fraccionaria_sin_truncarla(): void
    {
        $printable =
            $this->service()->build(
                $this->document(
                    quantity: 1.5,
                    unitPrice: 10000.0,
                    lineAmount: 15000.0,
                    netAmount: 15000.0,
                    taxAmount: 2850.0,
                    totalAmount: 17850.0,
                )
            );

        $item =
            $printable->items()[0];

        $this->assertSame(
            1.5,
            $item->quantity()
        );

        $this->assertSame(
            15000.0,
            $item->lineAmount()
        );
    }

    public function test_no_usa_raw_input_como_fuente_para_receptor_o_totales(): void
    {
        $document =
            $this->document(
                rawInput: [
                    'receiver' => [
                        'name' =>
                            'RECEPTOR INCORRECTO RAW',
                    ],
                    'totals' => [
                        'net' => 1,
                        'tax' => 2,
                        'total' => 3,
                    ],
                ]
            );

        $printable =
            $this->service()->build(
                $document
            );

        $this->assertSame(
            'Cliente dominio',
            $printable->receiver()->name()
        );

        $this->assertSame(
            40000.0,
            $printable->totals()->net()
        );

        $this->assertSame(
            47600.0,
            $printable->totals()->total()
        );
    }

    public function test_rechaza_documento_sin_folio(): void
    {
        $this->expectException(
            \DomainException::class
        );

        $this->expectExceptionMessage(
            'debe tener folio'
        );

        $this->service()->build(
            $this->document(
                folio: null
            )
        );
    }

    public function test_rechaza_empresa_inexistente(): void
    {
        $companyRepository =
            $this->createMock(
                CompanyRepositoryInterface::class
            );

        $companyRepository
            ->method('findById')
            ->willReturn(null);

        $locationRepository =
            $this->createMock(
                LocationRepositoryInterface::class
            );

        $service =
            new BuildDtePrintableDocumentService(
                companyRepository:
                    $companyRepository,
                locationRepository:
                    $locationRepository,
            );

        $this->expectException(
            CompanyNotFoundException::class
        );

        $service->build(
            $this->document()
        );
    }

    public function test_rechaza_ciudad_del_emisor_inexistente(): void
    {
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
            ->willReturn(null);

        $service =
            new BuildDtePrintableDocumentService(
                companyRepository:
                    $companyRepository,
                locationRepository:
                    $locationRepository,
            );

        $this->expectException(
            CityNotFoundException::class
        );

        $service->build(
            $this->document()
        );
    }
    public function test_emisor_sin_logo_no_genera_data_uri(): void
    {
        $printable =
            $this->service()
                ->build(
                    $this->document()
                );

        $this->assertNull(
            $printable
                ->issuer()
                ->logoDataUri()
        );
    }

    public function test_logo_valido_se_convierte_en_data_uri(): void
    {
        $contents =
            'contenido-binario-logo-valido';

        $logo =
            new CompanyLogoFile(
                path:
                    'dte/companies/1/branding/logo_test.png',
                mimeType:
                    'image/png',
                sha256:
                    hash(
                        'sha256',
                        $contents
                    ),
            );

        $company =
            $this->company()
                ->withLogo(
                    $logo
                );

        $storage =
            $this->createMock(
                CompanyLogoStorageInterface::class
            );

        $storage
            ->expects(
                $this->once()
            )
            ->method(
                'getContents'
            )
            ->with(
                $logo->path()
            )
            ->willReturn(
                $contents
            );

        $printable =
            $this->service(
                company:
                    $company,
                companyLogoStorage:
                    $storage,
            )->build(
                $this->document()
            );

        $this->assertSame(
            'data:image/png;base64,'
            . base64_encode(
                $contents
            ),
            $printable
                ->issuer()
                ->logoDataUri()
        );
    }

    public function test_logo_inexistente_no_impide_construir_documento(): void
    {
        $logo =
            new CompanyLogoFile(
                path:
                    'dte/companies/1/branding/logo_inexistente.png',
                mimeType:
                    'image/png',
                sha256:
                    str_repeat(
                        'a',
                        64
                    ),
            );

        $company =
            $this->company()
                ->withLogo(
                    $logo
                );

        $storage =
            $this->createMock(
                CompanyLogoStorageInterface::class
            );

        $storage
            ->expects(
                $this->once()
            )
            ->method(
                'getContents'
            )
            ->with(
                $logo->path()
            )
            ->willReturn(
                null
            );

        $printable =
            $this->service(
                company:
                    $company,
                companyLogoStorage:
                    $storage,
            )->build(
                $this->document()
            );

        $this->assertNull(
            $printable
                ->issuer()
                ->logoDataUri()
        );
    }

    public function test_logo_con_sha_alterado_no_se_incrusta(): void
    {
        $contents =
            'archivo-fisico-modificado';

        $logo =
            new CompanyLogoFile(
                path:
                    'dte/companies/1/branding/logo_test.png',
                mimeType:
                    'image/png',
                sha256:
                    str_repeat(
                        'a',
                        64
                    ),
            );

        $company =
            $this->company()
                ->withLogo(
                    $logo
                );

        $storage =
            $this->createMock(
                CompanyLogoStorageInterface::class
            );

        $storage
            ->expects(
                $this->once()
            )
            ->method(
                'getContents'
            )
            ->with(
                $logo->path()
            )
            ->willReturn(
                $contents
            );

        $printable =
            $this->service(
                company:
                    $company,
                companyLogoStorage:
                    $storage,
            )->build(
                $this->document()
            );

        $this->assertNull(
            $printable
                ->issuer()
                ->logoDataUri()
        );
    }
    private function service(
        ?Company $company = null,
        ?CompanyLogoStorageInterface $companyLogoStorage = null,
    ): BuildDtePrintableDocumentService {
        $companyRepository =
            $this->createMock(
                CompanyRepositoryInterface::class
            );

        $companyRepository
            ->method('findById')
            ->willReturn(
                $company
                ?? $this->company()
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

        return new BuildDtePrintableDocumentService(
            companyRepository:
                $companyRepository,
            locationRepository:
                $locationRepository,
            companyLogoStorage:
                $companyLogoStorage,
        );
    }

    private function company(): Company
    {
        return new Company(
            id: 1,
            rut: '77760724-3',
            rutBody: '77760724',
            rutDv: '3',
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

    private function document(
        float $quantity = 4.0,
        float $unitPrice = 10000.0,
        float $lineAmount = 40000.0,
        float $netAmount = 40000.0,
        float $taxAmount = 7600.0,
        float $totalAmount = 47600.0,
        ?int $folio = 35,
        ?array $rawInput = null,
    ): DteDocument {
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
                        'Cliente dominio',
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
                $netAmount,
            exemptAmount:
                0.0,
            taxAmount:
                $taxAmount,
            totalAmount:
                $totalAmount,

            items: [
                new DteLineItem(
                    lineNumber:
                        1,
                    itemCodeType:
                        null,
                    itemCode:
                        null,
                    name:
                        'Producto prueba',
                    description:
                        null,
                    quantity:
                        $quantity,
                    unitPrice:
                        $unitPrice,
                    discountPercent:
                        0.0,
                    discountAmount:
                        0.0,
                    taxExempt:
                        false,
                    lineAmount:
                        $lineAmount,
                    extraPayload:
                        null,
                ),
            ],

            references:
                [],

            headerPayload:
                null,

            totalsPayload: [
                'net' =>
                    $netAmount,
                'exempt' =>
                    0.0,
                'tax' =>
                    $taxAmount,
                'total' =>
                    $totalAmount,
            ],

            rawInput:
                $rawInput,

            folio:
                $folio,

            siiEnvironment:
                'cert',

            signedXmlPath:
                'signed/document.xml',

            tedXml:
                '<TED>TEST</TED>',
        );
    }
}