<?php
namespace App\Jobs\Dte\Automation;

use App\Jobs\Dte\Automation\AdvanceDteDocumentPipelineJob;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\Skip;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;

class PumpPendingDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct()
    {
        $this->onConnection((string) config('dte.automation.queue_connection'));
        $this->onQueue((string) config('dte.automation.queues.pipeline'));
        $this->afterCommit();
    }

    public function middleware():array
    {
        return [
            Skip::when(fn ():bool => !filter_var(config('dte.automation.enabled', true), FILTER_VALIDATE_BOOLEAN)),
        ];
    }

    public function handle(
        DteDocumentRepositoryInterface $documentRepository
    ):void{
        Context::add('job','PumpPendingDocumentsJob');

        $ids = $documentRepository->findIdsByStatuses(
            statuses:[
                DteStatus::READY_FOR_XML->value,
                DteStatus::FOLIO_ASSIGNED->value,
                DteStatus::XML_BUILT->value,
                DteStatus::TED_BUILT->value,
                DteStatus::SIGNED->value,
            ],
            limit: (int) config('dte.automation.limits.documents_per:pump', 50)
        );

        foreach($ids as $documentId)
        {
            AdvanceDteDocumentPipelineJob::dispatch($documentId)
                ->onConnection((string) config('dte.automation.queue_connection'))
                ->onQueue((string) config('dte.automation.queues.pipeline'));
        }
    }


}
