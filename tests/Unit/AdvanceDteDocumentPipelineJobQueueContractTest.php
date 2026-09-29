<?php

namespace Tests\Unit;

use App\Jobs\Dte\Automation\AdvanceDteDocumentPipelineJob;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Queue\Middleware\WithoutOverlapping;
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
}