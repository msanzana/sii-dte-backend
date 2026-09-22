<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\Services\DteXmlBuildDomainService;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use Tests\TestCase;
use App\Modules\Dte\Domain\Entities\DteLineItem;

class DteXmlBuildDomainServiceTest extends TestCase
{
    public function test_permite_reconstruir_xml_desde_needs_resend(): void
    {
        $document = new DteDocument(
            id: 39,
            externalId: 'boleta-reproceso',
            companyId: 1,
            dteType: DteType::BOLETA_ELECTRONICA,
            issueDate: '2026-09-17',
            status: DteStatus::NEEDS_RESEND->value,
            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Cliente prueba'
            ),
            netAmount: 40000,
            exemptAmount: 0,
            taxAmount: 7600,
            totalAmount: 47600,
            items: [
                new DteLineItem(
                    lineNumber: 1,
                    itemCodeType: null,
                    itemCode: null,
                    name: 'Producto de prueba',
                    description: null,
                    quantity: 1,
                    unitPrice: 40000,
                    discountPercent: 0,
                    discountAmount: 0,
                    taxExempt: false,
                    lineAmount: 40000,
                    extraPayload: null,
                ),
            ],
            folio: 1,
            siiEnvironment: 'cert',
            unsignedXmlPath: null,
            signedXmlPath: null,
            tedXml: null,
            externalSystemId: 1,
            cafId: 15,
            folioReservationId: 21,
        );

        $service = new DteXmlBuildDomainService();

        $service->assertCanBuildXml($document);

        $this->assertTrue(true);
    }
    public function test_no_permite_reconstruir_xml_desde_sent(): void
    {
        $document = new DteDocument(
            id: 39,
            externalId: 'boleta-sent',
            companyId: 1,
            dteType: DteType::BOLETA_ELECTRONICA,
            issueDate: '2026-09-17',
            status: DteStatus::SENT->value,
            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Cliente prueba'
            ),
            netAmount: 40000,
            exemptAmount: 0,
            taxAmount: 7600,
            totalAmount: 47600,
            items: [
                new DteLineItem(
                    lineNumber: 1,
                    itemCodeType: null,
                    itemCode: null,
                    name: 'Producto de prueba',
                    description: null,
                    quantity: 1,
                    unitPrice: 40000,
                    discountPercent: 0,
                    discountAmount: 0,
                    taxExempt: false,
                    lineAmount: 40000,
                    extraPayload: null,
                ),
            ],
            folio: 1,
            siiEnvironment: 'cert',
            unsignedXmlPath: null,
            signedXmlPath: null,
            tedXml: null,
            externalSystemId: 1,
            cafId: 15,
            folioReservationId: 21,
        );

        $service = new DteXmlBuildDomainService();

        $this->expectException(
            \App\Modules\Dte\Domain\Exceptions\InvalidDocumentStateException::class
        );

        $service->assertCanBuildXml($document);
    }
    public function test_permite_reconstruir_xml_built_si_proviene_de_reproceso_rsc_y_no_tiene_ted_ni_firma(): void
    {
        $document = new DteDocument(
            id: 39,
            externalId: 'boleta-rsc-xml-built',
            companyId: 1,
            dteType: DteType::BOLETA_ELECTRONICA,
            issueDate: '2026-09-17',
            status: DteStatus::XML_BUILT->value,
            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Cliente prueba'
            ),
            netAmount: 40000,
            exemptAmount: 0,
            taxAmount: 7600,
            totalAmount: 47600,
            items: [
                new DteLineItem(
                    lineNumber: 1,
                    itemCodeType: null,
                    itemCode: null,
                    name: 'Producto de prueba',
                    description: null,
                    quantity: 1,
                    unitPrice: 40000,
                    discountPercent: 0,
                    discountAmount: 0,
                    taxExempt: false,
                    lineAmount: 40000,
                    extraPayload: null,
                ),
            ],
            folio: 1,
            siiEnvironment: 'cert',
            unsignedXmlPath: 'app/private/dte/xml/xml_anterior.xml',
            signedXmlPath: null,
            tedXml: null,
            lastErrorCode: 'RSC',
            lastErrorMessage: 'DTE Rechazado',
            externalSystemId: 1,
            cafId: 15,
            folioReservationId: 21,
        );

        $service = new DteXmlBuildDomainService();

        $service->assertCanBuildXml($document);

        $this->assertTrue(true);
    }

    public function test_no_permite_reconstruir_xml_built_normal_sin_reproceso_rsc(): void
    {
        $document = new DteDocument(
            id: 39,
            externalId: 'boleta-xml-built-normal',
            companyId: 1,
            dteType: DteType::BOLETA_ELECTRONICA,
            issueDate: '2026-09-17',
            status: DteStatus::XML_BUILT->value,
            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Cliente prueba'
            ),
            netAmount: 40000,
            exemptAmount: 0,
            taxAmount: 7600,
            totalAmount: 47600,
            items: [
                new DteLineItem(
                    lineNumber: 1,
                    itemCodeType: null,
                    itemCode: null,
                    name: 'Producto de prueba',
                    description: null,
                    quantity: 1,
                    unitPrice: 40000,
                    discountPercent: 0,
                    discountAmount: 0,
                    taxExempt: false,
                    lineAmount: 40000,
                    extraPayload: null,
                ),
            ],
            folio: 1,
            siiEnvironment: 'cert',
            unsignedXmlPath: 'app/private/dte/xml/xml_normal.xml',
            signedXmlPath: null,
            tedXml: null,
            lastErrorCode: null,
            lastErrorMessage: null,
            externalSystemId: 1,
            cafId: 15,
            folioReservationId: 21,
        );

        $service = new DteXmlBuildDomainService();

        $this->expectException(
            \App\Modules\Dte\Domain\Exceptions\InvalidDocumentStateException::class
        );

        $service->assertCanBuildXml($document);
    }

    public function test_no_permite_rebuild_rsc_si_ya_existe_ted(): void
    {
        $document = new DteDocument(
            id: 39,
            externalId: 'boleta-rsc-con-ted',
            companyId: 1,
            dteType: DteType::BOLETA_ELECTRONICA,
            issueDate: '2026-09-17',
            status: DteStatus::XML_BUILT->value,
            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Cliente prueba'
            ),
            netAmount: 40000,
            exemptAmount: 0,
            taxAmount: 7600,
            totalAmount: 47600,
            items: [
                new DteLineItem(
                    lineNumber: 1,
                    itemCodeType: null,
                    itemCode: null,
                    name: 'Producto de prueba',
                    description: null,
                    quantity: 1,
                    unitPrice: 40000,
                    discountPercent: 0,
                    discountAmount: 0,
                    taxExempt: false,
                    lineAmount: 40000,
                    extraPayload: null,
                ),
            ],
            folio: 1,
            siiEnvironment: 'cert',
            unsignedXmlPath: 'app/private/dte/xml/xml_con_ted.xml',
            signedXmlPath: null,
            tedXml: '<TED version="1.0"></TED>',
            lastErrorCode: 'RSC',
            lastErrorMessage: 'DTE Rechazado',
            externalSystemId: 1,
            cafId: 15,
            folioReservationId: 21,
        );

        $service = new DteXmlBuildDomainService();

        $this->expectException(
            \App\Modules\Dte\Domain\Exceptions\InvalidDocumentStateException::class
        );

        $service->assertCanBuildXml($document);
    }

    public function test_no_permite_rebuild_rsc_si_ya_existe_xml_firmado(): void
    {
        $document = new DteDocument(
            id: 39,
            externalId: 'boleta-rsc-firmada',
            companyId: 1,
            dteType: DteType::BOLETA_ELECTRONICA,
            issueDate: '2026-09-17',
            status: DteStatus::XML_BUILT->value,
            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Cliente prueba'
            ),
            netAmount: 40000,
            exemptAmount: 0,
            taxAmount: 7600,
            totalAmount: 47600,
            items: [
                new DteLineItem(
                    lineNumber: 1,
                    itemCodeType: null,
                    itemCode: null,
                    name: 'Producto de prueba',
                    description: null,
                    quantity: 1,
                    unitPrice: 40000,
                    discountPercent: 0,
                    discountAmount: 0,
                    taxExempt: false,
                    lineAmount: 40000,
                    extraPayload: null,
                ),
            ],
            folio: 1,
            siiEnvironment: 'cert',
            unsignedXmlPath: 'app/private/dte/xml/xml_anterior.xml',
            signedXmlPath: 'app/private/dte/signed/xml_firmado.xml',
            tedXml: null,
            lastErrorCode: 'RSC',
            lastErrorMessage: 'DTE Rechazado',
            externalSystemId: 1,
            cafId: 15,
            folioReservationId: 21,
        );

        $service = new DteXmlBuildDomainService();

        $this->expectException(
            \App\Modules\Dte\Domain\Exceptions\InvalidDocumentStateException::class
        );

        $service->assertCanBuildXml($document);
    }
}
