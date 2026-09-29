<?php

namespace Tests\Unit;

use App\Jobs\Dte\Automation\ReconcileCafCountersJob;
use App\Jobs\Dte\Automation\SyncExpiredFolioReservationsJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tests\TestCase;

final class MaintenanceJobsQueueContractTest extends TestCase
{
    public function test_reconcile_caf_counters_implementa_el_contrato_queue_de_laravel(): void
    {
        $job = new ReconcileCafCountersJob();

        $this->assertInstanceOf(
            ShouldQueue::class,
            $job
        );
    }

    public function test_sync_expired_folio_reservations_implementa_el_contrato_queue_de_laravel(): void
    {
        $job = new SyncExpiredFolioReservationsJob();

        $this->assertInstanceOf(
            ShouldQueue::class,
            $job
        );
    }
    public function test_sync_expired_folio_reservations_configura_tres_intentos(): void
    {
        $job = new SyncExpiredFolioReservationsJob();

        $this->assertSame(
            3,
            $job->tries
        );
    }
}
