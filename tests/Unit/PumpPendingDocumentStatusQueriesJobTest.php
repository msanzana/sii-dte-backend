<?php

namespace Tests\Unit;

use App\Jobs\Dte\Automation\PumpPendingDocumentStatusQueriesJob;
use App\Jobs\Dte\Automation\QuerySingleDocumentStatusJob;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

final class PumpPendingDocumentStatusQueriesJobTest extends TestCase
{
    public function test_agenda_consultas_documentales_respetando_el_delay_configurado(): void
    {
        config([
            'cache.default' => 'array',
        ]);
        Queue::fake();

        Carbon::setTestNow('2026-09-22 05:00:00');

        config()->set(
            'dte.automation.delays.document_status_query_seconds',
            60
        );

        $repository = Mockery::mock(
            DteDocumentRepositoryInterface::class
        );

        $repository
            ->shouldReceive('findIdsByStatuses')
            ->once()
            ->with(
                [
                    DteStatus::SENDING->value,
                    DteStatus::SENT->value,
                ],
                50
            )
            ->andReturn([
                40,
            ]);

        $job = new PumpPendingDocumentStatusQueriesJob();

        $job->handle($repository);
        Queue::assertPushed(
            QuerySingleDocumentStatusJob::class,
            1
        );
        Queue::assertPushed(
            QuerySingleDocumentStatusJob::class,
            function (QuerySingleDocumentStatusJob $job): bool {
                return $job->documentId === 40
                    && $job->delay !== null
                    && Carbon::parse($job->delay)->equalTo(
                        now()->addSeconds(60)
                    );
            }
        );

        Carbon::setTestNow();
    }
}