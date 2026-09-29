<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\Services\HandleDocumentAutomationFailureService;
use App\Modules\Dte\Application\Services\ScheduleDocumentAutomationRetryService;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class HandleDocumentAutomationFailureServiceTest extends TestCase
{
    public function test_programa_siguiente_retry_y_lo_persiste(): void
    {
        config()->set(
            'dte.automation.document_retry.max_attempts',
            3
        );

        config()->set(
            'dte.automation.document_retry.backoff_seconds',
            [
                30,
                120,
                300,
            ]
        );

        $document =
            $this->documento();

        $repository =
            Mockery::mock(
                DteDocumentRepositoryInterface::class
            );

        $repository
            ->shouldReceive(
                'findByIdForUpdate'
            )
            ->once()
            ->with(1)
            ->andReturn(
                $document
            );

        $repository
            ->shouldReceive(
                'update'
            )
            ->once()
            ->with(
                Mockery::on(
                    function (
                        DteDocument $updated
                    ): bool {
                        return
                            $updated->status()
                                === DteStatus::FOLIO_ASSIGNED->value

                            && $updated->automationRetryAction()
                                === 'build_xml'

                            && $updated->automationRetryCount()
                                === 1

                            && $updated->automationNextRetryAt()
                                !== null

                            && $updated->lastErrorCode()
                                === 'AUTOMATION_BUILD_XML_FAILED'

                            && $updated->lastErrorMessage()
                                === 'Falló la construcción XML.';
                    }
                )
            )
            ->andReturnUsing(
                fn (
                    DteDocument $updated
                ): DteDocument =>
                    $updated
            );

        $service =
            new HandleDocumentAutomationFailureService(
                documentRepository:
                    $repository,

                scheduleRetryService:
                    new ScheduleDocumentAutomationRetryService()
            );

        $result =
            $service->execute(
                documentId:
                    1,

                action:
                    'build_xml',

                failure:
                    new RuntimeException(
                        'Falló la construcción XML.'
                    )
            );

        $this->assertSame(
            'build_xml',
            $result->automationRetryAction()
        );

        $this->assertSame(
            1,
            $result->automationRetryCount()
        );

        $this->assertNotNull(
            $result->automationNextRetryAt()
        );

        $this->assertSame(
            77,
            $result->folio()
        );

        $this->assertSame(
            15,
            $result->cafId()
        );

        $this->assertSame(
            21,
            $result->folioReservationId()
        );
    }

    public function test_al_fallar_el_ultimo_retry_lo_marca_agotado_y_no_lo_reprograma(): void
    {
        config()->set(
            'dte.automation.document_retry.max_attempts',
            3
        );

        config()->set(
            'dte.automation.document_retry.backoff_seconds',
            [
                30,
                120,
                300,
            ]
        );

        $document =
            $this->documento()
                ->withAutomationRetryScheduled(
                    action:
                        'build_xml',

                    nextRetryAt:
                        '2026-09-27 10:00:00',

                    errorCode:
                        'AUTOMATION_BUILD_XML_FAILED',

                    errorMessage:
                        'Fallo 1'
                )
                ->withAutomationRetryScheduled(
                    action:
                        'build_xml',

                    nextRetryAt:
                        '2026-09-27 10:02:00',

                    errorCode:
                        'AUTOMATION_BUILD_XML_FAILED',

                    errorMessage:
                        'Fallo 2'
                )
                ->withAutomationRetryScheduled(
                    action:
                        'build_xml',

                    nextRetryAt:
                        '2026-09-27 10:05:00',

                    errorCode:
                        'AUTOMATION_BUILD_XML_FAILED',

                    errorMessage:
                        'Fallo 3'
                );

        $this->assertSame(
            3,
            $document->automationRetryCount()
        );

        $repository =
            Mockery::mock(
                DteDocumentRepositoryInterface::class
            );

        $repository
            ->shouldReceive(
                'findByIdForUpdate'
            )
            ->once()
            ->with(1)
            ->andReturn(
                $document
            );

        $repository
            ->shouldReceive(
                'update'
            )
            ->once()
            ->with(
                Mockery::on(
                    function (
                        DteDocument $updated
                    ): bool {
                        return
                            $updated->automationRetryAction()
                                === 'build_xml'

                            && $updated->automationRetryCount()
                                === 3

                            && $updated->automationNextRetryAt()
                                === null

                            && $updated->lastErrorCode()
                                === 'AUTOMATION_BUILD_XML_RETRY_EXHAUSTED'

                            && str_contains(
                                (string) $updated->lastErrorMessage(),
                                'Último fallo'
                            );
                    }
                )
            )
            ->andReturnUsing(
                fn (
                    DteDocument $updated
                ): DteDocument =>
                    $updated
            );

        $service =
            new HandleDocumentAutomationFailureService(
                documentRepository:
                    $repository,

                scheduleRetryService:
                    new ScheduleDocumentAutomationRetryService()
            );

        $result =
            $service->execute(
                documentId:
                    1,

                action:
                    'build_xml',

                failure:
                    new RuntimeException(
                        'Último fallo'
                    )
            );

        $this->assertSame(
            3,
            $result->automationRetryCount()
        );

        $this->assertNull(
            $result->automationNextRetryAt()
        );

        $this->assertSame(
            'AUTOMATION_BUILD_XML_RETRY_EXHAUSTED',
            $result->lastErrorCode()
        );

        /*
        |--------------------------------------------------------------------------
        | Referencias jurídicas / de negocio siguen intactas
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            77,
            $result->folio()
        );

        $this->assertSame(
            15,
            $result->cafId()
        );

        $this->assertSame(
            21,
            $result->folioReservationId()
        );

        $this->assertSame(
            1,
            $result->externalSystemId()
        );
    }

    private function documento(): DteDocument
    {
        return new DteDocument(
            id:
                1,

            externalId:
                'automation-failure-handler-test',

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