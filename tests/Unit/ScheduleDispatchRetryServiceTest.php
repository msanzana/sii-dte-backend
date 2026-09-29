<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\Services\ScheduleDispatchRetryService;
use App\Modules\Dte\Domain\Entities\SiiDispatch;
use App\Modules\Dte\Domain\Enums\DispatchStatus;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class ScheduleDispatchRetryServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_programa_el_primer_retry_usando_la_configuracion(): void
    {
        config()->set(
            'dte.automation.dispatch_retry.max_attempts',
            3
        );

        config()->set(
            'dte.automation.dispatch_retry.backoff_seconds',
            [30, 120, 300]
        );

        Carbon::setTestNow(
            '2026-09-24 12:00:00'
        );

        $dispatch = new SiiDispatch(
            id: 10,
            batchUuid: '11111111-1111-1111-1111-111111111111',
            companyId: 1,
            dteDocumentId: 20,
            environment: 'cert',
            transportType: 'rest_upload_boleta',
            status: 'failed',
            retryCount: 0,
        );

        $service = new ScheduleDispatchRetryService();

        $scheduled = $service->execute(
            $dispatch
        );

        $this->assertSame(
            1,
            $scheduled->retryCount()
        );

        $this->assertSame(
            '2026-09-24 12:00:30',
            $scheduled->nextRetryAt()
        );

        $this->assertSame(
            'failed',
            $scheduled->status()
        );
    }

    public function test_segundo_retry_usa_el_segundo_backoff(): void
    {
        config()->set(
            'dte.automation.dispatch_retry.max_attempts',
            3
        );

        config()->set(
            'dte.automation.dispatch_retry.backoff_seconds',
            [30, 120, 300]
        );

        Carbon::setTestNow(
            '2026-09-24 12:00:00'
        );

        $dispatch = new SiiDispatch(
            id: 10,
            batchUuid: '11111111-1111-1111-1111-111111111111',
            companyId: 1,
            dteDocumentId: 20,
            environment: 'cert',
            transportType: 'soap_upload_factura',
            status: 'failed',
            retryCount: 1,
        );

        $service = new ScheduleDispatchRetryService();

        $scheduled = $service->execute(
            $dispatch
        );

        $this->assertSame(
            2,
            $scheduled->retryCount()
        );

        $this->assertSame(
            '2026-09-24 12:02:00',
            $scheduled->nextRetryAt()
        );
    }
    public function test_puede_programar_retry_mientras_no_alcanza_el_maximo(): void
    {
        config()->set(
            'dte.automation.dispatch_retry.max_attempts',
            3
        );

        config()->set(
            'dte.automation.dispatch_retry.backoff_seconds',
            [30, 120, 300]
        );

        $dispatch = $this->dispatch(
            status: DispatchStatus::FAILED->value,
            retryCount: 2
        );

        $service =
            new ScheduleDispatchRetryService();

        $this->assertTrue(
            $service->canScheduleRetry(
                $dispatch
            )
        );
    }

    public function test_no_puede_programar_retry_si_ya_alcanzo_el_maximo(): void
    {
        config()->set(
            'dte.automation.dispatch_retry.max_attempts',
            3
        );

        config()->set(
            'dte.automation.dispatch_retry.backoff_seconds',
            [30, 120, 300]
        );

        $dispatch = $this->dispatch(
            status: DispatchStatus::FAILED->value,
            retryCount: 3
        );

        $service =
            new ScheduleDispatchRetryService();

        $this->assertFalse(
            $service->canScheduleRetry(
                $dispatch
            )
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
}