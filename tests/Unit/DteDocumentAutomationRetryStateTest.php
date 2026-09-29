<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use Tests\TestCase;

final class DteDocumentAutomationRetryStateTest extends TestCase
{
    public function test_programa_retry_de_automatizacion_sin_perder_folio_caf_ni_reserva(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Documento que ya tiene folio asignado
        |--------------------------------------------------------------------------
        |
        | Representa, por ejemplo, un documento que va a ejecutar build_xml.
        |
        */

        $document =
            new DteDocument(
                id: 100,
                externalId:
                    '11111111-1111-1111-1111-111111111111',

                companyId: 1,

                dteType:
                    DteType::FACTURA_ELECTRTONICA,

                issueDate:
                    '2026-09-26',

                status:
                    DteStatus::FOLIO_ASSIGNED->value,

                receiver:
                    new ReceiverData(
                        document:
                            '66666666-6',

                        name:
                            'Cliente de prueba'
                    ),

                netAmount: 1000,
                exemptAmount: 0,
                taxAmount: 190,
                totalAmount: 1190,

                items: [],
                references: [],

                folio: 77,

                siiEnvironment:
                    'cert',

                externalSystemId: 1,

                cafId: 15,

                folioReservationId: 21
            );

        /*
        |--------------------------------------------------------------------------
        | Programar retry
        |--------------------------------------------------------------------------
        |
        | Todavía NO cambiamos el estado funcional del documento.
        |
        | El documento sigue en folio_assigned porque la acción que falló
        | fue build_xml.
        |
        */

        $retryDocument =
            $document
                ->withAutomationRetryScheduled(
                    action:
                        'build_xml',

                    nextRetryAt:
                        '2026-09-26 18:00:00',

                    errorCode:
                        'DTE_BUILD_XML_FAILED',

                    errorMessage:
                        'Falló la construcción del XML.'
                );

        /*
        |--------------------------------------------------------------------------
        | Estado funcional
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            DteStatus::FOLIO_ASSIGNED->value,
            $retryDocument->status()
        );

        /*
        |--------------------------------------------------------------------------
        | Información del retry
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            'build_xml',
            $retryDocument->automationRetryAction()
        );

        $this->assertSame(
            1,
            $retryDocument->automationRetryCount()
        );

        $this->assertSame(
            '2026-09-26 18:00:00',
            $retryDocument->automationNextRetryAt()
        );

        /*
        |--------------------------------------------------------------------------
        | Diagnóstico
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            'DTE_BUILD_XML_FAILED',
            $retryDocument->lastErrorCode()
        );

        $this->assertSame(
            'Falló la construcción del XML.',
            $retryDocument->lastErrorMessage()
        );

        /*
        |--------------------------------------------------------------------------
        | Protección del folio
        |--------------------------------------------------------------------------
        |
        | Este es uno de los requisitos centrales de la GTP.
        |
        */

        $this->assertSame(
            77,
            $retryDocument->folio()
        );

        $this->assertSame(
            15,
            $retryDocument->cafId()
        );

        $this->assertSame(
            21,
            $retryDocument->folioReservationId()
        );

        $this->assertSame(
            1,
            $retryDocument->externalSystemId()
        );
    }
    public function test_segundo_retry_incrementa_el_contador_sin_perder_referencias_de_negocio(): void
    {
        $document =
            new DteDocument(
                id: 100,
                externalId:
                    '11111111-1111-1111-1111-111111111111',

                companyId: 1,

                dteType:
                    DteType::FACTURA_ELECTRTONICA,

                issueDate:
                    '2026-09-26',

                status:
                    DteStatus::FOLIO_ASSIGNED->value,

                receiver:
                    new ReceiverData(
                        document:
                            '66666666-6',

                        name:
                            'Cliente de prueba'
                    ),

                netAmount: 1000,
                exemptAmount: 0,
                taxAmount: 190,
                totalAmount: 1190,

                items: [],
                references: [],

                folio: 77,

                siiEnvironment:
                    'cert',

                externalSystemId: 1,

                cafId: 15,

                folioReservationId: 21
            );

        /*
        |--------------------------------------------------------------------------
        | Primer retry
        |--------------------------------------------------------------------------
        */

        $firstRetry =
            $document
                ->withAutomationRetryScheduled(
                    action:
                        'build_xml',

                    nextRetryAt:
                        '2026-09-26 18:00:00',

                    errorCode:
                        'DTE_BUILD_XML_FAILED',

                    errorMessage:
                        'Primer fallo construyendo XML.'
                );

        /*
        |--------------------------------------------------------------------------
        | Segundo retry
        |--------------------------------------------------------------------------
        */

        $secondRetry =
            $firstRetry
                ->withAutomationRetryScheduled(
                    action:
                        'build_xml',

                    nextRetryAt:
                        '2026-09-26 18:02:00',

                    errorCode:
                        'DTE_BUILD_XML_FAILED',

                    errorMessage:
                        'Segundo fallo construyendo XML.'
                );

        /*
        |--------------------------------------------------------------------------
        | El contador debe avanzar
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            2,
            $secondRetry->automationRetryCount()
        );

        /*
        |--------------------------------------------------------------------------
        | Debe mantener la misma acción
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            'build_xml',
            $secondRetry->automationRetryAction()
        );

        /*
        |--------------------------------------------------------------------------
        | Debe reemplazar next_retry_at por el nuevo intento
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            '2026-09-26 18:02:00',
            $secondRetry->automationNextRetryAt()
        );

        /*
        |--------------------------------------------------------------------------
        | Debe conservar estado funcional
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            DteStatus::FOLIO_ASSIGNED->value,
            $secondRetry->status()
        );

        /*
        |--------------------------------------------------------------------------
        | Debe conservar el mismo folio
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            77,
            $secondRetry->folio()
        );

        /*
        |--------------------------------------------------------------------------
        | Debe conservar CAF
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            15,
            $secondRetry->cafId()
        );

        /*
        |--------------------------------------------------------------------------
        | Debe conservar reserva de folio
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            21,
            $secondRetry->folioReservationId()
        );

        /*
        |--------------------------------------------------------------------------
        | Debe conservar sistema externo
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            1,
            $secondRetry->externalSystemId()
        );

        /*
        |--------------------------------------------------------------------------
        | Debe actualizar diagnóstico
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            'DTE_BUILD_XML_FAILED',
            $secondRetry->lastErrorCode()
        );

        $this->assertSame(
            'Segundo fallo construyendo XML.',
            $secondRetry->lastErrorMessage()
        );
    }
}