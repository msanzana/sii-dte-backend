<?php

namespace Tests\Unit;

use App\Jobs\Dte\Automation\PumpPendingDocumentStatusQueriesJob;
use App\Jobs\Dte\Automation\QuerySingleDocumentStatusJob;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use App\Modules\Dte\Domain\Enums\DteStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            ->shouldReceive('findIdsEligibleForDocumentStatusQuery')
            ->once()
            ->with(50)
            ->andReturn([
                40,
            ]);
        $document = new DteDocument(
            id: 40,
            externalId: 'status-query-initial-40',
            companyId: 1,
            dteType: DteType::BOLETA_ELECTRONICA,
            issueDate: '2026-10-02',
            status: DteStatus::SENT->value,
            receiver: new ReceiverData(
                document: '11111111-1',
                name: 'Cliente prueba'
            ),
            netAmount: 10000,
            exemptAmount: 0,
            taxAmount: 1900,
            totalAmount: 11900,
            items: [],
            folio: 29,
            statusQueryRetryCount: 0,
            statusQueryNextAt: null
        );

        $repository
            ->shouldReceive('findById')
            ->once()
            ->with(40)
            ->andReturn($document);
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
    public function test_usa_seleccion_especifica_de_documentos_elegibles_para_consulta_de_estado(): void
    {
        $source = file_get_contents(
            app_path(
                'Jobs/Dte/Automation/PumpPendingDocumentStatusQueriesJob.php'
            )
        );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            'findIdsEligibleForDocumentStatusQuery',
            $source,
            'El pump debe usar una selección específica que respete el estado y la política temporal de consultas al SII.'
        );

        $this->assertStringNotContainsString(
            'findIdsByStatuses(',
            $source,
            'El pump no debe consultar indiscriminadamente todos los documentos SENDING/SENT.'
        );
    }
    public function test_retry_documental_vencido_no_agrega_un_segundo_delay(): void
    {
        config([
            'cache.default' => 'array',
        ]);

        Queue::fake();

        Carbon::setTestNow(
            '2026-10-02 05:12:11'
        );

        try {
            config()->set(
                'dte.automation.delays.document_status_query_seconds',
                60
            );

            $document = new DteDocument(
                id: 40,
                externalId: 'status-query-retry-40',
                companyId: 1,
                dteType: DteType::BOLETA_ELECTRONICA,
                issueDate: '2026-10-02',
                status: DteStatus::SENT->value,
                receiver: new ReceiverData(
                    document: '11111111-1',
                    name: 'Cliente prueba'
                ),
                netAmount: 10000,
                exemptAmount: 0,
                taxAmount: 1900,
                totalAmount: 11900,
                items: [],
                folio: 29,
                statusQueryRetryCount: 1,
                statusQueryNextAt: '2026-10-02 05:12:11'
            );

            $repository = Mockery::mock(
                DteDocumentRepositoryInterface::class
            );

            $repository
                ->shouldReceive(
                    'findIdsEligibleForDocumentStatusQuery'
                )
                ->once()
                ->with(50)
                ->andReturn([
                    40,
                ]);

            $repository
                ->shouldReceive('findById')
                ->once()
                ->with(40)
                ->andReturn($document);

            $job =
                new PumpPendingDocumentStatusQueriesJob();

            $job->handle($repository);

            Queue::assertPushed(
                QuerySingleDocumentStatusJob::class,
                function (
                    QuerySingleDocumentStatusJob $job
                ): bool {
                    return $job->documentId === 40
                        && $job->delay === null;
                }
            );
        } finally {
            Carbon::setTestNow();
        }
    }
    public function test_dos_pumps_consecutivos_no_duplican_consulta_documental_del_mismo_dte(): void
    {
        config([
            'cache.default' => 'array',
            'queue.default' => 'database',
            'dte.automation.queue_connection' => 'database',
            'dte.automation.queues.document_status' => 'dte-document-status',
            'dte.automation.delays.document_status_query_seconds' => 60,
        ]);

        Carbon::setTestNow('2026-10-03 06:00:00');

        $createdJobsTable = false;

        try {
            Cache::flush();

            /*
            |--------------------------------------------------------------------------
            | La suite unitaria usa SQLite :memory:
            |
            | Este archivo no ejecuta migraciones, por lo que construimos únicamente
            | la tabla mínima requerida por el driver database de Laravel.
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasTable('jobs')) {
                Schema::create('jobs', function (Blueprint $table): void {
                    $table->bigIncrements('id');
                    $table->string('queue')->index();
                    $table->longText('payload');
                    $table->unsignedTinyInteger('attempts');
                    $table->unsignedInteger('reserved_at')->nullable();
                    $table->unsignedInteger('available_at');
                    $table->unsignedInteger('created_at');
                });

                $createdJobsTable = true;
            }

            DB::table('jobs')
                ->where('queue', 'dte-document-status')
                ->delete();

            $document = new DteDocument(
                id: 4040,
                externalId: 'status-query-unique-4040',
                companyId: 1,
                dteType: DteType::FACTURA_ELECTRTONICA,
                issueDate: '2026-10-03',
                status: DteStatus::SENT->value,
                receiver: new ReceiverData(
                    document: '11111111-1',
                    name: 'Cliente prueba'
                ),
                netAmount: 10000,
                exemptAmount: 0,
                taxAmount: 1900,
                totalAmount: 11900,
                items: [],
                folio: 4040,
                statusQueryRetryCount: 0,
                statusQueryNextAt: null
            );

            $repository = Mockery::mock(
                DteDocumentRepositoryInterface::class
            );

            $repository
                ->shouldReceive(
                    'findIdsEligibleForDocumentStatusQuery'
                )
                ->twice()
                ->with(50)
                ->andReturn([4040]);

            $repository
                ->shouldReceive('findById')
                ->twice()
                ->with(4040)
                ->andReturn($document);

            $pump = new PumpPendingDocumentStatusQueriesJob();

            /*
            |--------------------------------------------------------------------------
            | El mismo documento aparece en dos pumps consecutivos
            |--------------------------------------------------------------------------
            */

            $pump->handle($repository);
            $pump->handle($repository);

            /*
            |--------------------------------------------------------------------------
            | ShouldBeUnique debe dejar una sola fila física
            |--------------------------------------------------------------------------
            */

            $jobs = DB::table('jobs')
                ->where('queue', 'dte-document-status')
                ->get();

            $this->assertCount(
                1,
                $jobs,
                'Dos pumps consecutivos generaron más de una consulta para el mismo DTE.'
            );

            /*
            |--------------------------------------------------------------------------
            | Confirmamos que el job retenido corresponde al DTE 4040
            |--------------------------------------------------------------------------
            */

            $payload = json_decode(
                $jobs->first()->payload,
                true
            );

            $queuedJob = unserialize(
                $payload['data']['command']
            );

            $this->assertInstanceOf(
                QuerySingleDocumentStatusJob::class,
                $queuedJob
            );

            $this->assertSame(
                4040,
                $queuedJob->documentId
            );
        } finally {
            if (Schema::hasTable('jobs')) {
                DB::table('jobs')
                    ->where('queue', 'dte-document-status')
                    ->delete();
            }

            Cache::flush();

            if ($createdJobsTable && Schema::hasTable('jobs')) {
                Schema::drop('jobs');
            }

            Carbon::setTestNow();
        }
    }
}