<?php
namespace App\Jobs\Dte\Automation;

use App\Modules\Dte\Application\Services\ReconcileAllCafCountersService;
use DragonCode\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;

final class ReconcileCafCountersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct()
    {
        $this->onConnection(
            (string) config(
                'dte.automation.queue_connection'
            )
        );
        $this->onQueue(
            (string) config
            (
                'dte.automation.queues.maintenance',
                'dte-maintenance'
            )
        );

        $this->afterCommit();
    }
    public function backOff():array
    {
        return [60,180,600];
    }

    public function handle(
        ReconcileAllCafCountersService $service
    ):void{
        Context::add(
            'job',
            'reconcileCafCountersJob'
        );

        $service->execute(
            (string) config(
                'dte.automation.limits.caf_counter_batch_size',
                200
            )
        );
    }
}