<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Entities\SiiDispatch;
use App\Modules\Dte\Domain\Enums\DispatchStatus;
use App\Modules\Dte\Domain\Services\DispatchRetryPolicyService;
use LogicException;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

final class DispatchRetryPolicyServiceTest extends TestCase
{
    public function test_failed_sin_retries_previos_puede_reintentarse_con_primer_backoff(): void
    {
        $policy = new DispatchRetryPolicyService(
            maxAttempts: 3,
            backoffSeconds: [30, 120, 300],
        );

        $dispatch = $this->dispatch(
            status: DispatchStatus::FAILED->value,
            retryCount: 0,
        );

        $this->assertTrue(
            $policy->canScheduleRetry($dispatch)
        );

        $this->assertSame(
            30,
            $policy->nextDelaySeconds($dispatch)
        );
    }

    public function test_el_backoff_depende_del_numero_de_retries_ya_realizados(): void
    {
        $policy = new DispatchRetryPolicyService(
            maxAttempts: 3,
            backoffSeconds: [30, 120, 300],
        );

        $secondRetry = $this->dispatch(
            status: DispatchStatus::FAILED->value,
            retryCount: 1,
        );

        $thirdRetry = $this->dispatch(
            status: DispatchStatus::FAILED->value,
            retryCount: 2,
        );

        $this->assertSame(
            120,
            $policy->nextDelaySeconds($secondRetry)
        );

        $this->assertSame(
            300,
            $policy->nextDelaySeconds($thirdRetry)
        );
    }

    public function test_no_permite_mas_retries_al_alcanzar_el_maximo(): void
    {
        $policy = new DispatchRetryPolicyService(
            maxAttempts: 3,
            backoffSeconds: [30, 120, 300],
        );

        $dispatch = $this->dispatch(
            status: DispatchStatus::FAILED->value,
            retryCount: 3,
        );

        $this->assertFalse(
            $policy->canScheduleRetry($dispatch)
        );

        $this->expectException(
            LogicException::class
        );

        $policy->nextDelaySeconds($dispatch);
    }

    public function test_delivery_unknown_nunca_es_elegible_para_retry_automatico(): void
    {
        $policy = new DispatchRetryPolicyService(
            maxAttempts: 3,
            backoffSeconds: [30, 120, 300],
        );

        $dispatch = $this->dispatch(
            status: DispatchStatus::DELIVERY_UNKNOWN->value,
            retryCount: 0,
        );

        $this->assertFalse(
            $policy->canScheduleRetry($dispatch)
        );
    }

    private function dispatch(
        string $status,
        int $retryCount,
        ?string $nextRetryAt = null
    ): SiiDispatch {
        return new SiiDispatch(
            id: 10,
            batchUuid: '11111111-1111-1111-1111-111111111111',
            companyId: 1,
            dteDocumentId: 20,
            environment: 'cert',
            transportType: 'soap_upload_factura',
            status: $status,
            retryCount: $retryCount,
            nextRetryAt: $nextRetryAt,
        );
    }
    public function test_retry_programado_y_vencido_puede_ejecutarse(): void
    {
        $policy = new DispatchRetryPolicyService(
            maxAttempts: 3,
            backoffSeconds: [30, 120, 300],
        );

        $dispatch = $this->dispatch(
            status: DispatchStatus::FAILED->value,
            retryCount: 1,
            nextRetryAt: '2026-09-25 02:59:59',
        );

        $this->assertTrue(
            $policy->canExecuteScheduledRetry(
                $dispatch,
                new DateTimeImmutable(
                    '2026-09-25 03:00:00'
                )
            )
        );
    }

    public function test_retry_programado_pero_aun_no_vencido_no_puede_ejecutarse(): void
    {
        $policy = new DispatchRetryPolicyService(
            maxAttempts: 3,
            backoffSeconds: [30, 120, 300],
        );

        $dispatch = $this->dispatch(
            status: DispatchStatus::FAILED->value,
            retryCount: 1,
            nextRetryAt: '2026-09-25 03:00:01',
        );

        $this->assertFalse(
            $policy->canExecuteScheduledRetry(
                $dispatch,
                new DateTimeImmutable(
                    '2026-09-25 03:00:00'
                )
            )
        );
    }

    public function test_failed_sin_next_retry_at_no_es_un_retry_programado(): void
    {
        $policy = new DispatchRetryPolicyService(
            maxAttempts: 3,
            backoffSeconds: [30, 120, 300],
        );

        $dispatch = $this->dispatch(
            status: DispatchStatus::FAILED->value,
            retryCount: 1,
            nextRetryAt: null,
        );

        $this->assertFalse(
            $policy->canExecuteScheduledRetry(
                $dispatch,
                new DateTimeImmutable(
                    '2026-09-25 03:00:00'
                )
            )
        );
    }

    public function test_el_ultimo_retry_programado_puede_ejecutarse_con_retry_count_maximo(): void
    {
        $policy = new DispatchRetryPolicyService(
            maxAttempts: 3,
            backoffSeconds: [30, 120, 300],
        );

        $dispatch = $this->dispatch(
            status: DispatchStatus::FAILED->value,
            retryCount: 3,
            nextRetryAt: '2026-09-25 02:59:59',
        );

        $this->assertTrue(
            $policy->canExecuteScheduledRetry(
                $dispatch,
                new DateTimeImmutable(
                    '2026-09-25 03:00:00'
                )
            )
        );
    }

    public function test_reconoce_un_retry_que_ya_fue_programado_aunque_aun_no_venza(): void
    {
        $policy = new DispatchRetryPolicyService(
            maxAttempts: 3,
            backoffSeconds: [30, 120, 300],
        );

        $dispatch = $this->dispatch(
            status: DispatchStatus::FAILED->value,
            retryCount: 1,
            nextRetryAt: '2026-09-26 10:00:30',
        );

        $this->assertTrue(
            $policy->hasScheduledRetry(
                $dispatch
            )
        );
    }

    public function test_failed_sin_next_retry_at_no_tiene_retry_programado(): void
    {
        $policy = new DispatchRetryPolicyService(
            maxAttempts: 3,
            backoffSeconds: [30, 120, 300],
        );

        $dispatch = $this->dispatch(
            status: DispatchStatus::FAILED->value,
            retryCount: 1,
            nextRetryAt: null,
        );

        $this->assertFalse(
            $policy->hasScheduledRetry(
                $dispatch
            )
        );
    }

    public function test_delivery_unknown_nunca_se_considera_retry_programado(): void
    {
        $policy = new DispatchRetryPolicyService(
            maxAttempts: 3,
            backoffSeconds: [30, 120, 300],
        );

        $dispatch = $this->dispatch(
            status: DispatchStatus::DELIVERY_UNKNOWN->value,
            retryCount: 1,
            nextRetryAt: '2026-09-26 10:00:30',
        );

        $this->assertFalse(
            $policy->hasScheduledRetry(
                $dispatch
            )
        );
    }

    public function test_retry_count_superior_al_maximo_no_es_un_retry_programado_valido(): void
    {
        $policy = new DispatchRetryPolicyService(
            maxAttempts: 3,
            backoffSeconds: [30, 120, 300],
        );

        $dispatch = $this->dispatch(
            status: DispatchStatus::FAILED->value,
            retryCount: 4,
            nextRetryAt: '2026-09-26 10:00:30',
        );

        $this->assertFalse(
            $policy->hasScheduledRetry(
                $dispatch
            )
        );
    }
}