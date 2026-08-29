<?php
namespace App\Jobs\Dte\Automation;

use App\Modules\Dte\Application\Services\SyncExpiredFolioReservationsService;
use DragonCode\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;

final class SyncExpiredFolioReservationsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $limits = 3;

    public function __construct()
    {
        $this->onConnection(
            (string) config('dte.automation.queue_connection')
        );

        $this->onQueue(
            (string) config(
                'dte.automation.queues.maintenance',
                'dte_maintenance'
            )
        );
        $this->afterCommit();
    }
    
    public function backoff():array{
        return [30,120,300];
    }

    public function handle(
        SyncExpiredFolioReservationsService $service
    ):void{
        Context::add(
            'job',
            'SyncExpiredDolioReservationsJob'
        );
        $service->execute(
            (int) config(
                'dte.automation.limits.expired_reservations_per_run',
                100
            )
        );
    }
}