<?php

namespace App\Jobs\Dte\Automation;

use App\Modules\Dte\Application\DTOs\QuerySiiDocumentStatusInputDto;
use App\Modules\Dte\Application\UseCases\Document\QuerySiiDocumentStatusUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\Skip;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;
use Throwable;

class QuerySingleDocumentStatusJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 300;

    public function __construct(
        public readonly int $documentId,
    ) {
        $this->onConnection((string) config('dte.automation.queue_connection'));
        $this->onQueue((string) config('dte.automation.queues.document_status'));
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return 'dte-document-status-query:' . $this->documentId;
    }

    public function tries(): int
    {
        return (int) config('dte.automation.tries.document_status', 5);
    }

    public function backoff(): array
    {
        return (array) config('dte.automation.backoff.document_status', [10, 60, 180]);
    }

    public function middleware(): array
    {
        return [
            Skip::when(fn (): bool => !filter_var(config('dte.automation.enabled', true), FILTER_VALIDATE_BOOLEAN)),
        ];
    }

    public function handle(
        QuerySiiDocumentStatusUseCase $querySiiDocumentStatusUseCase
    ): void {
        Context::add('job', 'QuerySingleDocumentStatusJob');
        Context::add('document_id', $this->documentId);

        $querySiiDocumentStatusUseCase->execute(
            new QuerySiiDocumentStatusInputDto(
                documentId: $this->documentId
            )
        );
    }

    public function failed(?Throwable $exception): void
    {
        logger()->error('Falló QuerySingleDocumentStatusJob.', [
            'document_id' => $this->documentId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
