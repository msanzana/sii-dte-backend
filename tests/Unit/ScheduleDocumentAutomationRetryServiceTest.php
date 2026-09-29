<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\Services\ScheduleDocumentAutomationRetryService;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use Carbon\Carbon;
use Tests\TestCase;

final class ScheduleDocumentAutomationRetryServiceTest extends TestCase
{
    private function makeDocument(
        int $retryCount = 0,
        ?string $retryAction = null,
        ?string $nextRetryAt = null
    ): DteDocument {
        return new DteDocument(
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

            folioReservationId: 21,

            automationRetryAction:
                $retryAction,

            automationRetryCount:
                $retryCount,

            automationNextRetryAt:
                $nextRetryAt
        );
    }

    public function test_programa_primer_retry_usando_la_configuracion(): void
    {
        Carbon::setTestNow(
            '2026-09-26 18:00:00'
        );

        $service =
            new ScheduleDocumentAutomationRetryService();

        $document =
            $this->makeDocument();

        $result =
            $service->execute(
                document:
                    $document,

                action:
                    'build_xml',

                errorCode:
                    'DTE_BUILD_XML_FAILED',

                errorMessage:
                    'Falló la construcción del XML.'
            );

        $this->assertSame(
            1,
            $result->automationRetryCount()
        );

        $this->assertSame(
            'build_xml',
            $result->automationRetryAction()
        );

        $this->assertSame(
            '2026-09-26 18:00:30',
            $result->automationNextRetryAt()
        );

        $this->assertSame(
            'DTE_BUILD_XML_FAILED',
            $result->lastErrorCode()
        );

        $this->assertSame(
            'Falló la construcción del XML.',
            $result->lastErrorMessage()
        );

        Carbon::setTestNow();
    }

    public function test_segundo_retry_usa_segundo_backoff(): void
    {
        Carbon::setTestNow(
            '2026-09-26 18:00:00'
        );

        $service =
            new ScheduleDocumentAutomationRetryService();

        $document =
            $this->makeDocument(
                retryCount: 1,
                retryAction: 'build_xml'
            );

        $result =
            $service->execute(
                document:
                    $document,

                action:
                    'build_xml',

                errorCode:
                    'DTE_BUILD_XML_FAILED',

                errorMessage:
                    'Segundo fallo.'
            );

        $this->assertSame(
            2,
            $result->automationRetryCount()
        );

        $this->assertSame(
            '2026-09-26 18:02:00',
            $result->automationNextRetryAt()
        );

        Carbon::setTestNow();
    }

    public function test_puede_programar_retry_mientras_no_alcanza_el_maximo(): void
    {
        $service =
            new ScheduleDocumentAutomationRetryService();

        $document =
            $this->makeDocument(
                retryCount: 2,
                retryAction: 'sign_xml'
            );

        $this->assertTrue(
            $service->canScheduleRetry(
                document:
                    $document,

                action:
                    'sign_xml'
            )
        );
    }

    public function test_no_puede_programar_retry_si_ya_alcanzo_el_maximo(): void
    {
        $service =
            new ScheduleDocumentAutomationRetryService();

        $document =
            $this->makeDocument(
                retryCount: 3,
                retryAction: 'sign_xml'
            );

        $this->assertFalse(
            $service->canScheduleRetry(
                document:
                    $document,

                action:
                    'sign_xml'
            )
        );
    }
}