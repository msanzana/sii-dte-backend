<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\Services\DteXmlDataAssemblerService;
use App\Modules\Dte\Domain\Entities\Company;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\ValueObjects\LocationSummary;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use Tests\TestCase;

class DteXmlDataAssemblerServiceTest extends TestCase
{
    public function test_boleta_propaga_ind_servicio_desde_header_payload(): void
    {
        $document = new DteDocument(
            id: 39,
            externalId: 'test-boleta-39',
            companyId: 1,
            dteType: DteType::BOLETA_ELECTRONICA,
            issueDate: '2026-09-18',
            status: 'folio_assigned',
            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Cliente prueba',
                giro: null,
                address: null,
                cityId: null,
                email: null,
            ),
            netAmount: 1000,
            exemptAmount: 0,
            taxAmount: 190,
            totalAmount: 1190,
            items: [],
            references: [],
            headerPayload: [
                'ind_servicio' => 3,
            ],
            folio: 1,
        );

        $company = new Company(
            id: 1,
            rut: '77760724-3',
            rutBody: '77760724',
            rutDv: '3',
            legalName: 'Empresa de prueba',
            tradeName: null,
            giro: 'COMERCIO PRODUCTOS',
            siiActivityCode: '479100',
            address: 'Dirección prueba',
            cityId: 1,
            dteEmail: 'empresa@prueba.cl',
            resolutionNumber: null,
            resolutionDate: null,
            siiEnvironment: 'cert',
            isActive: true,
        );

        $emitterLocation = new LocationSummary(
            cityId: 1,
            cityName: 'Temuco',
            comuneName: 'Temuco',
        );

        $service = new DteXmlDataAssemblerService();

        $data = $service->assemble(
            document: $document,
            company: $company,
            emitterLocation: $emitterLocation,
            receiverLocation: null,
        );

        $this->assertArrayHasKey(
            'ind_servicio',
            $data['id_doc']
        );

        $this->assertSame(
            '3',
            $data['id_doc']['ind_servicio']
        );
    
        $this->assertArrayHasKey(
            'ind_mnt_neto',
            $data['id_doc']
        );

        $this->assertSame(
            '2',
            $data['id_doc']['ind_mnt_neto']
        );
    }
    public function test_boleta_41_no_genera_ind_mnt_neto(): void
    {
        $document = new DteDocument(
            id: 41,
            externalId: 'test-boleta-41',
            companyId: 1,
            dteType: DteType::BOLETA_EXENTA_ELECTRONICA,
            issueDate: '2026-09-22',
            status: 'folio_assigned',
            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Cliente prueba',
                giro: null,
                address: null,
                cityId: null,
                email: null,
            ),
            netAmount: 0,
            exemptAmount: 1000,
            taxAmount: 0,
            totalAmount: 1000,
            items: [],
            references: [],
            headerPayload: [
                'ind_servicio' => 3,
            ],
            folio: 1,
        );

        $company = new Company(
            id: 1,
            rut: '77760724-3',
            rutBody: '77760724',
            rutDv: '3',
            legalName: 'Empresa de prueba',
            tradeName: null,
            giro: 'COMERCIO PRODUCTOS',
            siiActivityCode: '479100',
            address: 'Direccion prueba',
            cityId: 1,
            dteEmail: 'empresa@prueba.cl',
            resolutionNumber: null,
            resolutionDate: null,
            siiEnvironment: 'cert',
            isActive: true,
        );

        $emitterLocation = new LocationSummary(
            cityId: 1,
            cityName: 'Temuco',
            comuneName: 'Temuco',
        );

        $service = new DteXmlDataAssemblerService();

        $data = $service->assemble(
            document: $document,
            company: $company,
            emitterLocation: $emitterLocation,
            receiverLocation: null,
        );

        $this->assertSame(
            '3',
            $data['id_doc']['ind_servicio']
        );

        $this->assertArrayNotHasKey(
            'ind_mnt_neto',
            $data['id_doc']
        );
    }

    public function test_factura_33_no_genera_ind_mnt_neto(): void
    {
        $document = new DteDocument(
            id: 33,
            externalId: 'test-factura-33',
            companyId: 1,
            dteType: DteType::FACTURA_ELECTRTONICA,
            issueDate: '2026-09-22',
            status: 'folio_assigned',
            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Cliente factura',
                giro: 'SERVICIOS',
                address: 'Direccion receptor',
                cityId: null,
                email: null,
            ),
            netAmount: 1000,
            exemptAmount: 0,
            taxAmount: 190,
            totalAmount: 1190,
            items: [],
            references: [],
            headerPayload: [],
            folio: 100,
        );

        $company = new Company(
            id: 1,
            rut: '77760724-3',
            rutBody: '77760724',
            rutDv: '3',
            legalName: 'Empresa de prueba',
            tradeName: null,
            giro: 'COMERCIO PRODUCTOS',
            siiActivityCode: '479100',
            address: 'Direccion prueba',
            cityId: 1,
            dteEmail: 'empresa@prueba.cl',
            resolutionNumber: null,
            resolutionDate: null,
            siiEnvironment: 'cert',
            isActive: true,
        );

        $emitterLocation = new LocationSummary(
            cityId: 1,
            cityName: 'Temuco',
            comuneName: 'Temuco',
        );

        $service = new DteXmlDataAssemblerService();

        $data = $service->assemble(
            document: $document,
            company: $company,
            emitterLocation: $emitterLocation,
            receiverLocation: null,
        );

        $this->assertArrayNotHasKey(
            'ind_mnt_neto',
            $data['id_doc']
        );
    }
}