<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Entities\DteLineItem;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\Services\DteAutomationPlannerService;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use Tests\TestCase;

final class DteAutomationPlannerServiceTest extends TestCase
{
    public function test_ready_for_xml_prepara_el_documento_antes_de_construir_xml(): void
    {
        $document = $this->crearDocumento(
            status: DteStatus::READY_FOR_XML->value,
            dteType: DteType::BOLETA_ELECTRONICA,
        );

        $service = new DteAutomationPlannerService();

        $this->assertSame(
            'prepare_for_xml',
            $service->resolveNextAction($document)
        );
    }

    public function test_needs_resend_vuelve_a_construir_el_xml(): void
    {
        $document = $this->crearDocumento(
            status: DteStatus::NEEDS_RESEND->value,
            dteType: DteType::BOLETA_ELECTRONICA,
            lastErrorCode: 'RSC',
            lastErrorMessage: 'DTE Rechazado',
        );

        $service = new DteAutomationPlannerService();

        $this->assertSame(
            'build_xml',
            $service->resolveNextAction($document)
        );
    }

    public function test_signed_factura_se_enruta_al_envio_de_factura(): void
    {
        $document = $this->crearDocumento(
            status: DteStatus::SIGNED->value,
            dteType: DteType::FACTURA_ELECTRTONICA,
        );

        $service = new DteAutomationPlannerService();

        $this->assertSame(
            'send_factura',
            $service->resolveNextAction($document)
        );
    }

    public function test_signed_boleta_se_enruta_al_envio_de_boleta(): void
    {
        $document = $this->crearDocumento(
            status: DteStatus::SIGNED->value,
            dteType: DteType::BOLETA_ELECTRONICA,
        );

        $service = new DteAutomationPlannerService();

        $this->assertSame(
            'send_boleta',
            $service->resolveNextAction($document)
        );
    }

    private function crearDocumento(
        string $status,
        DteType $dteType,
        ?string $lastErrorCode = null,
        ?string $lastErrorMessage = null,
    ): DteDocument {
        return new DteDocument(
            id: 100,
            externalId: 'automatizacion-test',
            companyId: 1,
            dteType: $dteType,
            issueDate: '2026-09-22',
            status: $status,
            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Cliente prueba',
            ),
            netAmount: 10000,
            exemptAmount: 0,
            taxAmount: 1900,
            totalAmount: 11900,
            items: [
                new DteLineItem(
                    lineNumber: 1,
                    itemCodeType: null,
                    itemCode: null,
                    name: 'Producto prueba',
                    description: null,
                    quantity: 1,
                    unitPrice: 10000,
                    discountPercent: 0,
                    discountAmount: 0,
                    taxExempt: false,
                    lineAmount: 10000,
                    extraPayload: null,
                ),
            ],
            folio: 1,
            siiEnvironment: 'cert',
            unsignedXmlPath: null,
            signedXmlPath: null,
            tedXml: null,
            lastErrorCode: $lastErrorCode,
            lastErrorMessage: $lastErrorMessage,
            externalSystemId: 1,
            cafId: 15,
            folioReservationId: 21,
        );
    }
    public function test_prepare_for_xml_reencola_si_queda_folio_assigned(): void
    {
        $service = new DteAutomationPlannerService();

        $this->assertTrue(
            $service->shouldRequeueImmediately(
                action: 'prepare_for_xml',
                currentStatus: DteStatus::FOLIO_ASSIGNED->value
            )
        );
    }

    public function test_build_xml_reencola_si_queda_xml_built(): void
    {
        $service = new DteAutomationPlannerService();

        $this->assertTrue(
            $service->shouldRequeueImmediately(
                action: 'build_xml',
                currentStatus: DteStatus::XML_BUILT->value
            )
        );
    }

    public function test_build_ted_reencola_si_queda_ted_built(): void
    {
        $service = new DteAutomationPlannerService();

        $this->assertTrue(
            $service->shouldRequeueImmediately(
                action: 'build_ted',
                currentStatus: DteStatus::TED_BUILT->value
            )
        );
    }

    public function test_sign_xml_reencola_si_queda_signed(): void
    {
        $service = new DteAutomationPlannerService();

        $this->assertTrue(
            $service->shouldRequeueImmediately(
                action: 'sign_xml',
                currentStatus: DteStatus::SIGNED->value
            )
        );
    }

    public function test_send_factura_no_reencola_inmediatamente(): void
    {
        $service = new DteAutomationPlannerService();

        $this->assertFalse(
            $service->shouldRequeueImmediately(
                action: 'send_factura',
                currentStatus: DteStatus::SENT->value
            )
        );
    }

    public function test_send_boleta_no_reencola_inmediatamente(): void
    {
        $service = new DteAutomationPlannerService();

        $this->assertFalse(
            $service->shouldRequeueImmediately(
                action: 'send_boleta',
                currentStatus: DteStatus::SENT->value
            )
        );
    }

    public function test_accion_nula_no_reencola(): void
    {
        $service = new DteAutomationPlannerService();

        $this->assertFalse(
            $service->shouldRequeueImmediately(
                action: null,
                currentStatus: DteStatus::SIGNED->value
            )
        );
    }
}