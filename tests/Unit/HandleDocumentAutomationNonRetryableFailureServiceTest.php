<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\Services\HandleDocumentAutomationFailureService;
use App\Modules\Dte\Application\Services\ScheduleDocumentAutomationRetryService;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\Exceptions\InvalidDteException;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use Mockery;
use Tests\TestCase;

final class HandleDocumentAutomationNonRetryableFailureServiceTest
    extends TestCase
{
    public function test_error_deterministico_termina_sin_consumir_retries_automaticos(): void
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

        /*
        |--------------------------------------------------------------------------
        | Documento todavía sin retries consumidos
        |--------------------------------------------------------------------------
        */

        $document =
            new DteDocument(
                id:
                    1,

                externalId:
                    'non-retryable-document-test',

                companyId:
                    1,

                dteType:
                    DteType::BOLETA_ELECTRONICA,

                issueDate:
                    '2026-09-28',

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

                unsignedXmlPath:
                    'xml/invalido.xml',

                signedXmlPath:
                    'xml/firma_invalida.xml',

                tedXml:
                    '<TED>INVALIDO</TED>',

                externalSystemId:
                    1,

                cafId:
                    15,

                folioReservationId:
                    21
            );

        $failure =
            InvalidDteException::because(
                'El DTE contiene datos inválidos.'
            );

        /*
        |--------------------------------------------------------------------------
        | Repositorio
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | El error determinístico debe quedar terminal inmediatamente
        |--------------------------------------------------------------------------
        |
        | Importante:
        |
        | - no incrementa automation_retry_count;
        | - no genera automation_next_retry_at;
        | - conserva la acción que falló;
        | - usa un código distinto de RETRY_EXHAUSTED;
        | - conserva las referencias jurídicas/de negocio;
        | - aplica la limpieza técnica de build_xml.
        |
        */

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
                                === 0

                            && $updated->automationNextRetryAt()
                                === null

                            && $updated->lastErrorCode()
                                === 'AUTOMATION_BUILD_XML_NOT_RETRYABLE'

                            && $updated->lastErrorMessage()
                                === 'El DTE contiene datos inválidos.'

                            /*
                            | build_xml falló:
                            | ningún artefacto posterior es confiable.
                            */

                            && $updated->unsignedXmlPath()
                                === null

                            && $updated->tedXml()
                                === null

                            && $updated->signedXmlPath()
                                === null

                            /*
                            | Las referencias de negocio NO se pierden.
                            */

                            && $updated->folio()
                                === 77

                            && $updated->cafId()
                                === 15

                            && $updated->folioReservationId()
                                === 21

                            && $updated->externalSystemId()
                                === 1;
                    }
                )
            )
            ->andReturnUsing(
                fn (
                    DteDocument $updated
                ): DteDocument =>
                    $updated
            );

        /*
        |--------------------------------------------------------------------------
        | Handler actual
        |--------------------------------------------------------------------------
        |
        | Todavía NO inyectamos la nueva policy aquí.
        |
        | Este RED debe demostrar que hoy el handler programa un retry
        | incorrectamente para una DomainException.
        |
        */

        $service =
            new HandleDocumentAutomationFailureService(
                documentRepository:
                    $repository,

                scheduleRetryService:
                    new ScheduleDocumentAutomationRetryService()
            );

        $updated =
            $service->execute(
                documentId:
                    1,

                action:
                    'build_xml',

                failure:
                    $failure
            );

        /*
        |--------------------------------------------------------------------------
        | Estado devuelto
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            'build_xml',
            $updated->automationRetryAction()
        );

        $this->assertSame(
            0,
            $updated->automationRetryCount()
        );

        $this->assertNull(
            $updated->automationNextRetryAt()
        );

        $this->assertSame(
            'AUTOMATION_BUILD_XML_NOT_RETRYABLE',
            $updated->lastErrorCode()
        );
    }
}