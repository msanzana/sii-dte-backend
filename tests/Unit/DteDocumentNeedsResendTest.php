<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use DateTimeImmutable;
use Tests\TestCase;

class DteDocumentNeedsResendTest extends TestCase
{
    public function test_needs_resend_conserva_el_folio_actualiza_header_e_invalida_artefactos_derivados(): void
    {
        $sentAt = new DateTimeImmutable(
            '2026-09-18 20:45:44'
        );

        $document = new DteDocument(
            id: 39,
            externalId: '36569df0-08cd-4a87-81f6-a0ec7c37a5a5',
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
            items: [],
            references: [],
            headerPayload: null,
            folio: 1,
            siiEnvironment: 'cert',
            unsignedXmlPath:
                'app/private/dte/xml/dte_company_1_td_39_f_1_ted_antiguo.xml',
            signedXmlPath:
                'app/private/dte/signed/dte_company_1_td_39_f_1_signed_antiguo.xml',
            tedXml: '<TED>antiguo</TED>',
            externalSystemId: 1,
            cafId: 15,
            folioReservationId: 21,
            branchOfficeNumber: 1,
            facilityNumber: 1,
            externalBranchCode: 'TEMUCO-CENTRO',
            sentAt: $sentAt,
        );

        $reprocessDocument = $document->withNeedsResend(
            headerPayloadPatch: [
                'ind_servicio' => 3,
            ],
            code: 'RSC',
            message: 'DTE Rechazado'
        );

        $this->assertSame(
            DteStatus::NEEDS_RESEND->value,
            $reprocessDocument->status()
        );

        $this->assertSame(
            [
                'ind_servicio' => 3,
            ],
            $reprocessDocument->headerPayload()
        );

        /*
         * El mismo DTE conserva exactamente el mismo folio
         * y sus asociaciones tributarias.
         */
        $this->assertSame(
            1,
            $reprocessDocument->folio()
        );

        $this->assertSame(
            15,
            $reprocessDocument->cafId()
        );

        $this->assertSame(
            21,
            $reprocessDocument->folioReservationId()
        );

        $this->assertSame(
            1,
            $reprocessDocument->externalSystemId()
        );

        /*
         * Al cambiar información semántica del DTE,
         * todos los artefactos derivados dejan de ser válidos.
         */
        $this->assertNull(
            $reprocessDocument->unsignedXmlPath()
        );

        $this->assertNull(
            $reprocessDocument->signedXmlPath()
        );

        $this->assertNull(
            $reprocessDocument->tedXml()
        );

        /*
         * Conservamos el diagnóstico que originó el reproceso.
         */
        $this->assertSame(
            'RSC',
            $reprocessDocument->lastErrorCode()
        );

        $this->assertSame(
            'DTE Rechazado',
            $reprocessDocument->lastErrorMessage()
        );

        /*
         * El nuevo ciclo todavía no ha sido enviado.
         * El intento anterior queda históricamente registrado
         * en sii_dispatches.
         */
        $this->assertNull(
            $reprocessDocument->queuedAt()
        );

        $this->assertNull(
            $reprocessDocument->sentAt()
        );

        $this->assertNull(
            $reprocessDocument->acceptedAt()
        );

        $this->assertNull(
            $reprocessDocument->rejectedAt()
        );

        /*
         * La entidad original permanece intacta.
         */
        $this->assertSame(
            DteStatus::SENT->value,
            $document->status()
        );

        $this->assertSame(
            'app/private/dte/signed/dte_company_1_td_39_f_1_signed_antiguo.xml',
            $document->signedXmlPath()
        );

        $this->assertSame(
            $sentAt,
            $document->sentAt()
        );
    }
}