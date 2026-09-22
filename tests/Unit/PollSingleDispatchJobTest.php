<?php

namespace Tests\Unit;

use App\Jobs\Dte\Automation\PollSingleDispatchJob;
use App\Jobs\Dte\Automation\QuerySingleDocumentStatusJob;
use App\Modules\Dte\Application\DTOs\PollBoletaDispatchStatusResultDto;
use App\Modules\Dte\Application\DTOs\PollSiiUploadStatusResultDto;
use App\Modules\Dte\Application\UseCases\Dispatch\PollBoletaDispatchStatusUseCase;
use App\Modules\Dte\Application\UseCases\Dispatch\PollSiiUploadStatusUseCase;
use App\Modules\Dte\Domain\Entities\SiiDispatch;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

final class PollSingleDispatchJobTest extends TestCase
{
    public function test_factura_usa_polling_soap_y_agenda_consulta_documental_al_quedar_processed(): void
    {
        Queue::fake();

        $dispatch = new SiiDispatch(
            id: 100,
            batchUuid: 'batch-factura',
            companyId: 1,
            dteDocumentId: 10,
            environment: 'cert',
            transportType: 'soap_upload_factura',
            status: 'upload_ok',
            trackId: '123456',
        );

        $repository = Mockery::mock(
            SiiDispatchRepositoryInterface::class
        );

        $repository
            ->shouldReceive('findById')
            ->once()
            ->with(100)
            ->andReturn($dispatch);

        $facturaPolling = Mockery::mock(
            PollSiiUploadStatusUseCase::class
        );

        $facturaPolling
            ->shouldReceive('execute')
            ->once()
            ->andReturn(
                new PollSiiUploadStatusResultDto(
                    dispatchId: 100,
                    status: 'processed',
                    trackId: '123456',
                    uploadStatusCode: 'EPR',
                    uploadStatusMessage: null,
                    responseBody: '{}',
                )
            );

        $boletaPolling = Mockery::mock(
            PollBoletaDispatchStatusUseCase::class
        );

        $boletaPolling
            ->shouldNotReceive('execute');

        $job = new PollSingleDispatchJob(
            dispatchId: 100
        );

        $job->handle(
            dispatchRepository: $repository,
            pollSiiUploadStatusUseCase: $facturaPolling,
            pollBoletaDispatchStatusUseCase: $boletaPolling,
        );

        Queue::assertPushed(
            QuerySingleDocumentStatusJob::class,
            fn (QuerySingleDocumentStatusJob $job): bool =>
                $job->documentId === 10
        );
    }

    public function test_boleta_usa_polling_rest_y_agenda_consulta_documental_al_quedar_processed(): void
    {
        Queue::fake();

        $dispatch = new SiiDispatch(
            id: 101,
            batchUuid: 'batch-boleta',
            companyId: 1,
            dteDocumentId: 40,
            environment: 'cert',
            transportType: 'rest_upload_boleta',
            status: 'sent',
            trackId: '32197087',
        );

        $repository = Mockery::mock(
            SiiDispatchRepositoryInterface::class
        );

        $repository
            ->shouldReceive('findById')
            ->once()
            ->with(101)
            ->andReturn($dispatch);

        $facturaPolling = Mockery::mock(
            PollSiiUploadStatusUseCase::class
        );

        $facturaPolling
            ->shouldNotReceive('execute');

        $boletaPolling = Mockery::mock(
            PollBoletaDispatchStatusUseCase::class
        );

        $boletaPolling
            ->shouldReceive('execute')
            ->once()
            ->andReturn(
                new PollBoletaDispatchStatusResultDto(
                    dispatchId: 101,
                    status: 'processed',
                    trackId: '32197087',
                    sendStatusCode: 'EPR',
                    sendStatusMessage: null,
                    rawBody: '{}',
                )
            );

        $job = new PollSingleDispatchJob(
            dispatchId: 101
        );

        $job->handle(
            dispatchRepository: $repository,
            pollSiiUploadStatusUseCase: $facturaPolling,
            pollBoletaDispatchStatusUseCase: $boletaPolling,
        );

        Queue::assertPushed(
            QuerySingleDocumentStatusJob::class,
            fn (QuerySingleDocumentStatusJob $job): bool =>
                $job->documentId === 40
        );
    }
}