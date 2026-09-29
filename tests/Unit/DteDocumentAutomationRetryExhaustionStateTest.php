<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use Tests\TestCase;

final class DteDocumentAutomationRetryExhaustionStateTest
    extends TestCase
{
    public function test_agota_retry_interno_sin_perder_referencias_de_negocio(): void
    {
        $document =
            $this->documento()
                ->withAutomationRetryScheduled(
                    action:
                        'build_xml',

                    nextRetryAt:
                        '2026-09-27 06:10:00',

                    errorCode:
                        'AUTOMATION_BUILD_XML_FAILED',

                    errorMessage:
                        'Primer fallo.'
                )
                ->withAutomationRetryScheduled(
                    action:
                        'build_xml',

                    nextRetryAt:
                        '2026-09-27 06:12:00',

                    errorCode:
                        'AUTOMATION_BUILD_XML_FAILED',

                    errorMessage:
                        'Segundo fallo.'
                )
                ->withAutomationRetryScheduled(
                    action:
                        'build_xml',

                    nextRetryAt:
                        '2026-09-27 06:15:00',

                    errorCode:
                        'AUTOMATION_BUILD_XML_FAILED',

                    errorMessage:
                        'Tercer retry programado.'
                );

        $this->assertSame(
            3,
            $document->automationRetryCount()
        );

        $this->assertNotNull(
            $document->automationNextRetryAt()
        );

        $exhausted =
            $document->withAutomationRetryExhausted(
                action:
                    'build_xml',

                errorCode:
                    'AUTOMATION_BUILD_XML_RETRY_EXHAUSTED',

                errorMessage:
                    'Se agotaron los retries automáticos de build_xml.'
            );

        /*
        |--------------------------------------------------------------------------
        | Conserva la etapa original
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            DteStatus::FOLIO_ASSIGNED->value,
            $exhausted->status()
        );

        /*
        |--------------------------------------------------------------------------
        | Conserva diagnóstico del retry agotado
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            'build_xml',
            $exhausted->automationRetryAction()
        );

        $this->assertSame(
            3,
            $exhausted->automationRetryCount()
        );

        /*
        |--------------------------------------------------------------------------
        | Fundamental: deja de ser elegible para el pump
        |--------------------------------------------------------------------------
        */

        $this->assertNull(
            $exhausted->automationNextRetryAt()
        );

        $this->assertSame(
            'AUTOMATION_BUILD_XML_RETRY_EXHAUSTED',
            $exhausted->lastErrorCode()
        );

        $this->assertSame(
            'Se agotaron los retries automáticos de build_xml.',
            $exhausted->lastErrorMessage()
        );

        /*
        |--------------------------------------------------------------------------
        | Nunca pierde las referencias de negocio
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            77,
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
    }

    private function documento(): DteDocument
    {
        return new DteDocument(
            id:
                1,

            externalId:
                'automation-retry-exhaustion-test',

            companyId:
                1,

            dteType:
                DteType::BOLETA_ELECTRONICA,

            issueDate:
                '2026-09-27',

            status:
                DteStatus::FOLIO_ASSIGNED->value,

            receiver:
                new ReceiverData(
                    document:
                        '11111111-1',

                    name:
                        'Cliente prueba'
                ),

            netAmount:
                1000,

            exemptAmount:
                0,

            taxAmount:
                190,

            totalAmount:
                1190,

            items:
                [],

            folio:
                77,

            siiEnvironment:
                'cert',

            externalSystemId:
                1,

            cafId:
                15,

            folioReservationId:
                21
        );
    }
}