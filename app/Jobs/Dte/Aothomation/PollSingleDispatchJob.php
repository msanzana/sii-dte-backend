<?php

namespace App\Jobs\Dte\Automation;

use App\Jobs\Dte\Automation\QuerySingleDocumentStatusJob;
use App\Modules\Dte\Application\DTOs\PollBoletaDispatchStatusInputDto;
use App\Modules\Dte\Application\DTOs\PollSiiUploadStatusInputDto;
use App\Modules\Dte\Application\UseCases\Dispatch\PollBoletaDispatchStatusUseCase;
use App\Modules\Dte\Application\UseCases\Dispatch\PollSiiUploadStatusUseCase;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\Skip;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;
use Throwable;

class PollSingleDispatchJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 300;

    public function __construct(
        public readonly int $dispatchId,
    ) {
        $this->onConnection((string) config('dte.automation.queue_connection'));
        $this->onQueue((string) config('dte.automation.queues.dispatch_polling'));
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return 'dte-dispatch-poll:' . $this->dispatchId;
    }

    public function tries(): int
    {
        return (int) config('dte.automation.tries.dispatch_polling', 5);
    }

    public function backoff(): array
    {
        return (array) config('dte.automation.backoff.dispatch_polling', [10, 30, 120]);
    }

    public function middleware(): array
    {
        return [
            Skip::when(fn (): bool => !filter_var(config('dte.automation.enabled', true), FILTER_VALIDATE_BOOLEAN)),
        ];
    }

    public function handle(
        SiiDispatchRepositoryInterface $dispatchRepository,
        PollSiiUploadStatusUseCase $pollSiiUploadStatusUseCase,
        PollBoletaDispatchStatusUseCase $pollBoletaDispatchStatusUseCase,
    ): void {
        Context::add('job', 'PollSingleDispatchJob');
        Context::add('dispatch_id', $this->dispatchId);

        $dispatch = $dispatchRepository->findById($this->dispatchId);

        if (!$dispatch) {
            return;
        }

        if ($dispatch->transportType() === 'soap_upload_factura') {
            $result = $pollSiiUploadStatusUseCase->execute(
                new PollSiiUploadStatusInputDto(
                    dispatchId: $this->dispatchId
                )
            );

            if (
                $result->status === 'processed'
                && $dispatch->dteDocumentId() !== null
            ) {
                QuerySingleDocumentStatusJob::dispatch($dispatch->dteDocumentId())
                    ->delay(now()->addSeconds(
                        (int) config('dte.automation.delays.document_status_query_seconds', 60)
                    ))
                    ->onConnection((string) config('dte.automation.queue_connection'))
                    ->onQueue((string) config('dte.automation.queues.document_status'));
            }

            return;
        }

        if ($dispatch->transportType() === 'rest_upload_boleta') {
            $result = $pollBoletaDispatchStatusUseCase->execute(
                new PollBoletaDispatchStatusInputDto(
                    dispatchId: $this->dispatchId
                )
            );

            if (
                $result->status === 'processed'
                && $dispatch->dteDocumentId() !== null
            ) {
                QuerySingleDocumentStatusJob::dispatch($dispatch->dteDocumentId())
                    ->delay(now()->addSeconds(
                        (int) config('dte.automation.delays.document_status_query_seconds', 60)
                    ))
                    ->onConnection((string) config('dte.automation.queue_connection'))
                    ->onQueue((string) config('dte.automation.queues.document_status'));
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        logger()->error('Falló PollSingleDispatchJob.', [
            'dispatch_id' => $this->dispatchId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
