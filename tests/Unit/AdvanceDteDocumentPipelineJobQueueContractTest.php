<?php

namespace Tests\Unit;

use App\Jobs\Dte\Automation\AdvanceDteDocumentPipelineJob;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdvanceDteDocumentPipelineJobQueueContractTest extends TestCase
{
    public function test_libera_el_lock_unico_antes_de_procesar_para_permitir_requeue_inmediato(): void
    {
        $job = new AdvanceDteDocumentPipelineJob(
            documentId: 123
        );

        $this->assertInstanceOf(
            ShouldBeUniqueUntilProcessing::class,
            $job
        );
    }

    public function test_impide_procesar_simultaneamente_el_mismo_documento(): void
    {
        $job = new AdvanceDteDocumentPipelineJob(
            documentId: 123
        );

        $middlewares = $job->middleware();

        $hasWithoutOverlapping = collect($middlewares)
            ->contains(
                fn (object $middleware): bool =>
                    $middleware instanceof WithoutOverlapping
            );

        $this->assertTrue(
            $hasWithoutOverlapping,
            'AdvanceDteDocumentPipelineJob debe usar WithoutOverlapping para evitar procesamiento concurrente del mismo documento.'
        );
    }

    public function test_dispatch_normal_no_duplica_fisicamente_el_job_del_mismo_documento(): void
    {
        $createdJobsTable = $this->prepareDatabaseQueue();

        try {
            $first = AdvanceDteDocumentPipelineJob::dispatch(9001);
            unset($first);
            gc_collect_cycles();

            $second = AdvanceDteDocumentPipelineJob::dispatch(9001);
            unset($second);
            gc_collect_cycles();

            $jobs = DB::table('jobs')
                ->where('queue', 'dte-pipeline')
                ->orderBy('id')
                ->get();

            $this->assertCount(
                1,
                $jobs,
                'Dos dispatch normales del mismo DTE deben producir solamente un job físico mientras exista el lock único.'
            );

            $payload = json_decode(
                $jobs->first()->payload,
                true
            );

            $command = unserialize(
                $payload['data']['command']
            );

            $this->assertInstanceOf(
                AdvanceDteDocumentPipelineJob::class,
                $command
            );

            $this->assertSame(
                9001,
                $command->documentId
            );
        } finally {
            $this->cleanupDatabaseQueue(
                $createdJobsTable
            );
        }
    }

    public function test_codigo_productivo_no_despacha_pipeline_mediante_bus_dispatcher_de_bajo_nivel(): void
    {
        $files = array_merge(
            File::allFiles(app_path()),
            File::allFiles(base_path('routes'))
        );

        $violations = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $source = File::get(
                $file->getPathname()
            );

            /*
             * Sólo nos interesa código que construya manualmente
             * AdvanceDteDocumentPipelineJob.
             *
             * La vía permitida es:
             *
             * AdvanceDteDocumentPipelineJob::dispatch($documentId)
             *
             * o self::dispatch(...) dentro del propio job.
             */
            $constructsPipelineJob =
                preg_match(
                    '/new\s+\\\\?(?:App\\\\Jobs\\\\Dte\\\\Automation\\\\)?AdvanceDteDocumentPipelineJob\s*\(/',
                    $source
                ) === 1;

            if (!$constructsPipelineJob) {
                continue;
            }

            /*
             * Dispatcher de bajo nivel puede evitar la adquisición
             * del lock de ShouldBeUnique / ShouldBeUniqueUntilProcessing.
             */
            $usesLowLevelDispatcher =
                preg_match(
                    '/Dispatcher::class|Bus::dispatch\s*\(/',
                    $source
                ) === 1;

            if ($usesLowLevelDispatcher) {
                $violations[] = str_replace(
                    base_path() . DIRECTORY_SEPARATOR,
                    '',
                    $file->getPathname()
                );
            }
        }

        $this->assertSame(
            [],
            $violations,
            'AdvanceDteDocumentPipelineJob no debe despacharse construyéndolo manualmente mediante Bus Dispatcher. Archivos: '
                . implode(', ', $violations)
        );
    }

    private function prepareDatabaseQueue(): bool
    {
        config([
            'cache.default' => 'array',

            'queue.default' => 'database',

            'queue.connections.database' => [
                'driver' => 'database',
                'connection' => null,
                'table' => 'jobs',
                'queue' => 'default',
                'retry_after' => 90,
                'after_commit' => false,
            ],

            'dte.automation.queue_connection' =>
                'database',

            'dte.automation.queues.pipeline' =>
                'dte-pipeline',
        ]);

        Cache::flush();

        $createdJobsTable =
            !Schema::hasTable('jobs');

        if ($createdJobsTable) {
            Schema::create(
                'jobs',
                function (Blueprint $table): void {
                    $table->bigIncrements('id');
                    $table->string('queue')->index();
                    $table->longText('payload');
                    $table->unsignedTinyInteger('attempts');
                    $table->unsignedInteger('reserved_at')
                        ->nullable();
                    $table->unsignedInteger('available_at');
                    $table->unsignedInteger('created_at');
                }
            );
        }

        DB::table('jobs')->delete();

        return $createdJobsTable;
    }

    private function cleanupDatabaseQueue(
        bool $createdJobsTable
    ): void {
        if (Schema::hasTable('jobs')) {
            DB::table('jobs')->delete();
        }

        Cache::flush();

        if (
            $createdJobsTable
            && Schema::hasTable('jobs')
        ) {
            Schema::drop('jobs');
        }
    }
}