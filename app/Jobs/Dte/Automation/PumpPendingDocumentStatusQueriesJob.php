<?php
namespace App\Jobs\Dte\Automation;

use App\Jobs\Dte\Automation\QuerySingleDocumentStatusJob;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\Skip;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;

class PumpPendingDocumentStatusQueriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onConnection((string) config('dte.automation.queue_connection'));
        $this->onQueue((string) config('dte.automation.queues.document_status'));
        $this->afterCommit();
    }

    public function middleware(): array
    {
        return [
            Skip::when(fn (): bool => !filter_var(config('dte.automation.enabled', true), FILTER_VALIDATE_BOOLEAN)),
        ];
    }

    public function handle(
        DteDocumentRepositoryInterface $documentRepository
    ):void
    {
        Context::add('job','PumpPendingDocumentStatusQueriesJob');

        $ids = $documentRepository->findIdsByStatuses(
            statuses: [DteStatus::SENDING->value,
                        DteStatus::SENT->value,],
            limit:(int) config('dte.automation.limits.document_status_queries_per_pump',50)
        );

        foreach($ids as $documentId)
        {
            QuerySingleDocumentStatusJob::dispatch($documentId)
                        ->onConnection((string) config('dte.automation.queue_connection'))
                        ->onQueue((string) config('dte.automation.queues.document_status'));
        }
    }
}
