<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\Services\DocumentAutomationRetryPolicyService;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use DateTimeImmutable;
use LogicException;
use Tests\TestCase;

final class DocumentAutomationRetryPolicyServiceTest extends TestCase
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

    public function test_build_xml_puede_programar_primer_retry(): void
    {
        $policy =
            new DocumentAutomationRetryPolicyService(
                maxAttempts: 3,
                backoffSeconds: [
                    30,
                    120,
                    300,
                ]
            );

        $document =
            $this->makeDocument();

        $this->assertTrue(
            $policy->canScheduleRetry(
                document:
                    $document,

                action:
                    'build_xml'
            )
        );

        $this->assertSame(
            30,
            $policy->nextDelaySeconds(
                document:
                    $document,

                action:
                    'build_xml'
            )
        );
    }

    public function test_segundo_retry_usa_segundo_backoff(): void
    {
        $policy =
            new DocumentAutomationRetryPolicyService(
                maxAttempts: 3,
                backoffSeconds: [
                    30,
                    120,
                    300,
                ]
            );

        $document =
            $this->makeDocument(
                retryCount: 1,
                retryAction: 'build_xml'
            );

        $this->assertSame(
            120,
            $policy->nextDelaySeconds(
                document:
                    $document,

                action:
                    'build_xml'
            )
        );
    }

    public function test_no_permite_mas_retries_al_alcanzar_el_maximo(): void
    {
        $policy =
            new DocumentAutomationRetryPolicyService(
                maxAttempts: 3,
                backoffSeconds: [
                    30,
                    120,
                    300,
                ]
            );

        $document =
            $this->makeDocument(
                retryCount: 3,
                retryAction: 'build_xml'
            );

        $this->assertFalse(
            $policy->canScheduleRetry(
                document:
                    $document,

                action:
                    'build_xml'
            )
        );
    }

    public function test_solo_permite_acciones_internas_retryables(): void
    {
        $policy =
            new DocumentAutomationRetryPolicyService(
                maxAttempts: 3,
                backoffSeconds: [
                    30,
                    120,
                    300,
                ]
            );

        $document =
            $this->makeDocument();

        $this->assertTrue(
            $policy->canScheduleRetry(
                document:
                    $document,

                action:
                    'build_xml'
            )
        );

        $this->assertTrue(
            $policy->canScheduleRetry(
                document:
                    $document,

                action:
                    'build_ted'
            )
        );

        $this->assertTrue(
            $policy->canScheduleRetry(
                document:
                    $document,

                action:
                    'sign_xml'
            )
        );

        $this->assertFalse(
            $policy->canScheduleRetry(
                document:
                    $document,

                action:
                    'send'
            )
        );
    }

    public function test_retry_programado_y_vencido_puede_ejecutarse(): void
    {
        $policy =
            new DocumentAutomationRetryPolicyService(
                maxAttempts: 3,
                backoffSeconds: [
                    30,
                    120,
                    300,
                ]
            );

        $document =
            $this->makeDocument(
                retryCount: 1,
                retryAction: 'build_xml',
                nextRetryAt:
                    '2026-09-26 18:00:00'
            );

        $this->assertTrue(
            $policy->canExecuteScheduledRetry(
                document:
                    $document,

                action:
                    'build_xml',

                now:
                    new DateTimeImmutable(
                        '2026-09-26 18:00:00'
                    )
            )
        );
    }

    public function test_retry_programado_pero_aun_no_vencido_no_puede_ejecutarse(): void
    {
        $policy =
            new DocumentAutomationRetryPolicyService(
                maxAttempts: 3,
                backoffSeconds: [
                    30,
                    120,
                    300,
                ]
            );

        $document =
            $this->makeDocument(
                retryCount: 1,
                retryAction: 'build_xml',
                nextRetryAt:
                    '2026-09-26 18:00:00'
            );

        $this->assertFalse(
            $policy->canExecuteScheduledRetry(
                document:
                    $document,

                action:
                    'build_xml',

                now:
                    new DateTimeImmutable(
                        '2026-09-26 17:59:59'
                    )
            )
        );
    }

    public function test_no_ejecuta_retry_si_la_accion_programada_no_coincide(): void
    {
        $policy =
            new DocumentAutomationRetryPolicyService(
                maxAttempts: 3,
                backoffSeconds: [
                    30,
                    120,
                    300,
                ]
            );

        $document =
            $this->makeDocument(
                retryCount: 1,
                retryAction: 'build_xml',
                nextRetryAt:
                    '2026-09-26 18:00:00'
            );

        $this->assertFalse(
            $policy->canExecuteScheduledRetry(
                document:
                    $document,

                action:
                    'build_ted',

                now:
                    new DateTimeImmutable(
                        '2026-09-26 18:00:00'
                    )
            )
        );
    }

    public function test_next_delay_lanza_error_si_no_es_elegible(): void
    {
        $policy =
            new DocumentAutomationRetryPolicyService(
                maxAttempts: 3,
                backoffSeconds: [
                    30,
                    120,
                    300,
                ]
            );

        $document =
            $this->makeDocument(
                retryCount: 3,
                retryAction: 'build_xml'
            );

        $this->expectException(
            LogicException::class
        );

        $policy->nextDelaySeconds(
            document:
                $document,

            action:
                'build_xml'
        );
    }
}