<?php

namespace App\Jobs\Dte\Automation;

use App\Modules\Dte\Application\DTOs\AdvanceDteAutomationInputDto;
use App\Modules\Dte\Application\UseCases\Automation\AdvanceDteAutomationUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\Skip;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;
use Throwable;

class AdvanceDteDocumentPipelineJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 300;

    public function __construct(
        public readonly int $documentId,
    ) {
        $this->onConnection((string) config('dte.automation.queue_connection'));
        $this->onQueue((string) config('dte.automation.queues.pipeline'));
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return 'dte-document-pipeline:' . $this->documentId;
    }

    public function tries(): int
    {
        return (int) config('dte.automation.tries.pipeline', 5);
    }

    public function backoff(): array
    {
        return (array) config('dte.automation.backoff.pipeline', [5, 15, 60]);
    }

    public function middleware(): array
    {
        return [
            Skip::when(fn (): bool => !filter_var(config('dte.automation.enabled', true), FILTER_VALIDATE_BOOLEAN)),
        ];
    }

    public function handle(
        AdvanceDteAutomationUseCase $advanceDteAutomationUseCase
    ): void {
        Context::add('job', 'AdvanceDteDocumentPipelineJob');
        Context::add('document_id', $this->documentId);

        $result = $advanceDteAutomationUseCase->execute(
            new AdvanceDteAutomationInputDto(
                documentId: $this->documentId
            )
        );

        if ($result->shouldRequeueImmediately) {
            self::dispatch($this->documentId)
                ->delay(now()->addSeconds(
                    (int) config('dte.automation.delays.immediate_requeue_seconds', 1)
                ))
                ->onConnection((string) config('dte.automation.queue_connection'))
                ->onQueue((string) config('dte.automation.queues.pipeline'));
        }
    }

    public function failed(?Throwable $exception): void
    {
        logger()->error('Falló AdvanceDteDocumentPipelineJob.', [
            'document_id' => $this->documentId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
