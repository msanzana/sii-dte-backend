<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Entities\SiiDispatch;
use LogicException;
use PHPUnit\Framework\TestCase;

final class SiiDispatchRetryStateTest extends TestCase
{
    public function test_failed_puede_programar_retry_e_incrementa_el_contador(): void
    {
        $dispatch = new SiiDispatch(
            id: 10,
            batchUuid: '11111111-1111-1111-1111-111111111111',
            companyId: 1,
            dteDocumentId: 20,
            environment: 'cert',
            transportType: 'soap_upload_factura',
            status: 'failed',
            retryCount: 1,
            nextRetryAt: null,
            errorMessage: 'Fallo de autenticación.',
        );

        $scheduled = $dispatch->withScheduledRetry(
            '2026-09-24 03:00:00'
        );

        $this->assertSame(
            2,
            $scheduled->retryCount()
        );

        $this->assertSame(
            '2026-09-24 03:00:00',
            $scheduled->nextRetryAt()
        );

        $this->assertSame(
            'failed',
            $scheduled->status()
        );

        $this->assertSame(
            $dispatch->id(),
            $scheduled->id()
        );

        $this->assertSame(
            $dispatch->batchUuid(),
            $scheduled->batchUuid()
        );

        $this->assertSame(
            $dispatch->dteDocumentId(),
            $scheduled->dteDocumentId()
        );
    }

    public function test_no_permite_programar_retry_si_el_dispatch_no_esta_failed(): void
    {
        $dispatch = new SiiDispatch(
            id: 10,
            batchUuid: '11111111-1111-1111-1111-111111111111',
            companyId: 1,
            dteDocumentId: 20,
            environment: 'cert',
            transportType: 'soap_upload_factura',
            status: 'delivery_unknown',
        );

        $this->expectException(
            LogicException::class
        );

        $dispatch->withScheduledRetry(
            '2026-09-24 03:00:00'
        );
    }
}