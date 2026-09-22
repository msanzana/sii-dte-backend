<?php

namespace Tests\Unit;

use App\Jobs\Dte\Automation\PollSingleDispatchJob;
use App\Jobs\Dte\Automation\PumpPendingDispatchPollingJob;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

final class PumpPendingDispatchPollingJobTest extends TestCase
{
    public function test_busca_dispatches_enviados_de_factura_y_boleta_para_polling(): void
    {
        Queue::fake();

        $repository = Mockery::mock(
            SiiDispatchRepositoryInterface::class
        );

        $repository
            ->shouldReceive('findIdsByStatusesAndTransportTypes')
            ->once()
            ->with(
                [
                    'upload_ok',
                    'sent',
                    'polling',
                ],
                [
                    'soap_upload_factura',
                    'rest_upload_boleta',
                ],
                50
            )
            ->andReturn([
                42,
                43,
            ]);

        $job = new PumpPendingDispatchPollingJob();

        $job->handle($repository);

        Queue::assertPushed(
            PollSingleDispatchJob::class,
            2
        );

        Queue::assertPushed(
            PollSingleDispatchJob::class,
            fn (PollSingleDispatchJob $job): bool =>
                $job->dispatchId === 42
        );

        Queue::assertPushed(
            PollSingleDispatchJob::class,
            fn (PollSingleDispatchJob $job): bool =>
                $job->dispatchId === 43
        );
    }
}