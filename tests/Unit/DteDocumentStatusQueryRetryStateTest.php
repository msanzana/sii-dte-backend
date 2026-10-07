<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;

use Tests\TestCase;

final class DteDocumentStatusQueryRetryStateTest extends TestCase
{
    public function test_programa_retry_de_consulta_sin_perder_referencias_de_negocio(): void
    {
        $document = $this->document(
            statusQueryRetryCount: 0,
            statusQueryNextAt: null
        );

        $retried = $document->withStatusQueryRetryScheduled(
            nextRetryAt: '2026-10-01 01:30:00',
            code: 'FAU',
            message: 'Documento No Recibido por el SII'
        );

        $this->assertSame(
            DteStatus::SENT->value,
            $retried->status()
        );

        $this->assertSame(
            1,
            $retried->statusQueryRetryCount()
        );

        $this->assertSame(
            '2026-10-01 01:30:00',
            $retried->statusQueryNextAt()
        );

        $this->assertSame(
            'FAU',
            $retried->lastErrorCode()
        );

        $this->assertSame(
            'Documento No Recibido por el SII',
            $retried->lastErrorMessage()
        );

        /*
        |--------------------------------------------------------------------------
        | Identidad y referencias de negocio
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            29,
            $retried->folio()
        );

        $this->assertSame(
            15,
            $retried->cafId()
        );

        $this->assertSame(
            21,
            $retried->folioReservationId()
        );

        $this->assertSame(
            1,
            $retried->externalSystemId()
        );

        /*
        |--------------------------------------------------------------------------
        | Los artefactos siguen existiendo mientras sólo reconsultamos
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            'unsigned.xml',
            $retried->unsignedXmlPath()
        );

        $this->assertSame(
            'signed.xml',
            $retried->signedXmlPath()
        );

        $this->assertSame(
            '<TED />',
            $retried->tedXml()
        );
    }

    public function test_retries_sucesivos_incrementan_el_contador(): void
    {
        $document = $this->document(
            statusQueryRetryCount: 1,
            statusQueryNextAt: '2026-10-01 01:30:00'
        );

        $retried = $document->withStatusQueryRetryScheduled(
            nextRetryAt: '2026-10-01 01:33:00',
            code: 'FAU',
            message: 'Documento No Recibido por el SII'
        );

        $this->assertSame(
            2,
            $retried->statusQueryRetryCount()
        );

        $this->assertSame(
            '2026-10-01 01:33:00',
            $retried->statusQueryNextAt()
        );

        $this->assertSame(
            29,
            $retried->folio()
        );

        $this->assertSame(
            15,
            $retried->cafId()
        );

        $this->assertSame(
            21,
            $retried->folioReservationId()
        );
    }

    public function test_agotamiento_pasa_a_needs_resend_sin_perder_folio_caf_ni_reserva(): void
    {
        $document = $this->document(
            statusQueryRetryCount: 3,
            statusQueryNextAt: '2026-10-01 01:40:00'
        );

        $exhausted =
            $document->withStatusQueryRetryExhausted(
                code: 'FAU',
                message: 'Documento No Recibido por el SII'
            );

        $this->assertSame(
            DteStatus::NEEDS_RESEND->value,
            $exhausted->status()
        );

        /*
        |--------------------------------------------------------------------------
        | El folio fiscal permanece intacto
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            29,
            $exhausted->folio()
        );

        $this->assertSame(
            15,
            $exhausted->cafId()
        );

        $this->assertSame(
            21,
            $exhausted->folioReservationId()
        );

        $this->assertSame(
            1,
            $exhausted->externalSystemId()
        );

        /*
        |--------------------------------------------------------------------------
        | El retry de consulta queda agotado
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            3,
            $exhausted->statusQueryRetryCount()
        );

        $this->assertNull(
            $exhausted->statusQueryNextAt()
        );

        /*
        |--------------------------------------------------------------------------
        | Se invalidan artefactos técnicos para reconstrucción
        |--------------------------------------------------------------------------
        */

        $this->assertNull(
            $exhausted->unsignedXmlPath()
        );

        $this->assertNull(
            $exhausted->signedXmlPath()
        );

        $this->assertNull(
            $exhausted->tedXml()
        );

        /*
        |--------------------------------------------------------------------------
        | Se conserva el diagnóstico SII
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            'FAU',
            $exhausted->lastErrorCode()
        );

        $this->assertSame(
            'Documento No Recibido por el SII',
            $exhausted->lastErrorMessage()
        );
    }
    public function test_transiciones_de_envio_y_terminales_inician_ciclo_de_consulta_limpio_sin_perder_referencias(): void
    {
        $document = $this->document(
            statusQueryRetryCount: 2,
            statusQueryNextAt: '2026-10-03 10:00:00'
        );

        $transitions = [
            DteStatus::SENT->value =>
                $document->withSentStatus(),

            DteStatus::ACCEPTED->value =>
                $document->withAcceptedStatus(),

            DteStatus::ACCEPTED_WITH_REPAROS->value =>
                $document->withAcceptedWithReparosStatus(
                    'RPR',
                    'Documento aceptado con reparos'
                ),

            DteStatus::REJECTED->value =>
                $document->withRejectedStatus(
                    'RCH',
                    'Documento rechazado'
                ),
        ];

        foreach ($transitions as $expectedStatus => $transitioned) {
            /*
            |--------------------------------------------------------------------------
            | Estado esperado
            |--------------------------------------------------------------------------
            */

            $this->assertSame(
                $expectedStatus,
                $transitioned->status()
            );

            /*
            |--------------------------------------------------------------------------
            | Cada nuevo ciclo debe partir sin retry de consulta anterior
            |--------------------------------------------------------------------------
            */

            $this->assertSame(
                0,
                $transitioned->statusQueryRetryCount(),
                "El estado {$expectedStatus} conservó un contador de consulta anterior."
            );

            $this->assertNull(
                $transitioned->statusQueryNextAt(),
                "El estado {$expectedStatus} conservó una fecha de retry anterior."
            );

            /*
            |--------------------------------------------------------------------------
            | Las referencias de negocio nunca deben perderse
            |--------------------------------------------------------------------------
            */

            $this->assertSame(
                29,
                $transitioned->folio()
            );

            $this->assertSame(
                15,
                $transitioned->cafId()
            );

            $this->assertSame(
                21,
                $transitioned->folioReservationId()
            );

            $this->assertSame(
                1,
                $transitioned->externalSystemId()
            );
        }
    }
    private function document(
        int $statusQueryRetryCount,
        ?string $statusQueryNextAt
    ): DteDocument {
        return new DteDocument(
            id: 37,
            externalId: 'test-status-query-retry',
            companyId: 1,
            dteType: DteType::FACTURA_ELECTRTONICA,
            issueDate: '2026-10-01',
            status: DteStatus::SENT->value,

            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Receptor de prueba'
            ),

            netAmount: 10000,
            exemptAmount: 0,
            taxAmount: 1900,
            totalAmount: 11900,

            items: [],
            references: [],

            headerPayload: [
                'test' => true,
            ],

            totalsPayload: null,
            rawInput: [
                'source' => 'test',
            ],

            folio: 29,
            siiEnvironment: 'certification',

            unsignedXmlPath: 'unsigned.xml',
            signedXmlPath: 'signed.xml',
            tedXml: '<TED />',

            lastErrorCode: 'FAU',
            lastErrorMessage:
                'Documento No Recibido por el SII',

            externalSystemId: 1,
            cafId: 15,
            folioReservationId: 21,

            branchOfficeNumber: 1,
            facilityNumber: 1,
            externalBranchCode: 'TEMUCO-CENTRO',

            automationRetryAction: null,
            automationRetryCount: 0,
            automationNextRetryAt: null,

            statusQueryRetryCount:
                $statusQueryRetryCount,

            statusQueryNextAt:
                $statusQueryNextAt,
        );
    }
    public function test_rebuild_xml_despues_de_fau_agotado_inicia_nuevo_ciclo_de_consulta(): void
    {
        $document = new DteDocument(
            id: 37,
            externalId: 'status-query-rebuild-test',
            companyId: 1,
            dteType: DteType::BOLETA_ELECTRONICA,
            issueDate: '2026-10-02',
            status: DteStatus::NEEDS_RESEND->value,
            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Cliente prueba'
            ),
            netAmount: 10000,
            exemptAmount: 0,
            taxAmount: 1900,
            totalAmount: 11900,
            items: [],
            folio: 29,
            siiEnvironment: 'cert',
            externalSystemId: 1,
            cafId: 15,
            folioReservationId: 21,

            /*
            |--------------------------------------------------------------------------
            | Ciclo FAU anterior agotado
            |--------------------------------------------------------------------------
            */

            statusQueryRetryCount: 3,
            statusQueryNextAt: null
        );

        $rebuilt = $document->withUnsignedXmlBuilt(
            'app/private/dte/xml/rebuilt.xml'
        );

        /*
        |--------------------------------------------------------------------------
        | Nuevo ciclo técnico
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            DteStatus::XML_BUILT->value,
            $rebuilt->status()
        );

        $this->assertSame(
            0,
            $rebuilt->statusQueryRetryCount()
        );

        $this->assertNull(
            $rebuilt->statusQueryNextAt()
        );

        /*
        |--------------------------------------------------------------------------
        | El reproceso NO debe consumir otro folio
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            29,
            $rebuilt->folio()
        );

        $this->assertSame(
            15,
            $rebuilt->cafId()
        );

        $this->assertSame(
            21,
            $rebuilt->folioReservationId()
        );

        $this->assertSame(
            1,
            $rebuilt->externalSystemId()
        );

        $this->assertSame(
            'app/private/dte/xml/rebuilt.xml',
            $rebuilt->unsignedXmlPath()
        );
    }
    public function test_fau_agotado_limpia_retry_de_automatizacion_anterior(): void
    {
        $document = new DteDocument(
            id: 37,
            externalId: 'status-query-stale-automation-retry',
            companyId: 1,
            dteType: DteType::BOLETA_ELECTRONICA,
            issueDate: '2026-10-02',
            status: DteStatus::SENT->value,
            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Cliente prueba'
            ),
            netAmount: 10000,
            exemptAmount: 0,
            taxAmount: 1900,
            totalAmount: 11900,
            items: [],
            folio: 29,
            externalSystemId: 1,
            cafId: 15,
            folioReservationId: 21,

            automationRetryAction: 'build_xml',
            automationRetryCount: 2,
            automationNextRetryAt: '2026-10-02 06:00:00',

            statusQueryRetryCount: 3,
            statusQueryNextAt: null
        );

        $exhausted = $document->withStatusQueryRetryExhausted(
            code: 'FAU',
            message: 'Documento No Recibido por el SII'
        );

        $this->assertSame(
            DteStatus::NEEDS_RESEND->value,
            $exhausted->status()
        );

        $this->assertNull(
            $exhausted->automationRetryAction()
        );

        $this->assertSame(
            0,
            $exhausted->automationRetryCount()
        );

        $this->assertNull(
            $exhausted->automationNextRetryAt()
        );

        $this->assertSame(
            29,
            $exhausted->folio()
        );

        $this->assertSame(
            15,
            $exhausted->cafId()
        );

        $this->assertSame(
            21,
            $exhausted->folioReservationId()
        );

        $this->assertSame(
            1,
            $exhausted->externalSystemId()
        );

        $this->assertSame(
            3,
            $exhausted->statusQueryRetryCount()
        );

        $this->assertNull(
            $exhausted->statusQueryNextAt()
        );
    }
}