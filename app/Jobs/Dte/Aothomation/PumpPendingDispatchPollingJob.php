<?php
namespace App\Jobs\Dte\Aothomation;

use App\Jobs\Dte\Automation\PollSingleDispatchJob;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\Skip;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;

class PumpPendingDispatchPollingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct()
    {
        $this->onConnection((string) config('dte.automation.queue_connection'));
        $this->onQueue((string) config('dte.automation.queues.dispatch_polling'));
        $this->afterCommit();
    }

    public function middleware():array
    {
        return [
            Skip::when(fn ():bool => !filter_var(config('dte.automation.enabled', true), FILTER_VALIDATE_BOOLEAN))
        ];
    }

    public function handle(
        SiiDispatchRepositoryInterface $dispatchRepository
    ):void
    {
        Context::add('job','pumpPendingDispatchPollingJob');

        $ids = $dispatchRepository->findIdsByStatusesAndTransportTypes(
            statuses: ['send','polling'],
            transportTypes: ['soap_upload_factura','soap_upload_boleta'],
            limit: (int) config('dte.automation.limits.dispatches_per_pump', 50)
        );

        foreach($ids as $dispatchId)
        {
            PollSingleDispatchJob::dispatch($dispatchId)
                ->onConnection((string) config('dte.automation.queue_connection'))
                ->onQueue((string) config('dte.automation.queues.dispatch_polling'));
        }
    }

}
