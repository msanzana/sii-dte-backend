<?php

namespace Tests\Unit;

use App\Jobs\Dte\Automation\AdvanceDteDocumentPipelineJob;
use ReflectionClass;
use Tests\TestCase;

final class AdvanceDteDocumentPipelineJobScheduledRetryContractTest extends TestCase
{
    public function test_captura_el_retry_de_negocio_programado_sin_relanzarlo(): void
    {
        $reflection =
            new ReflectionClass(
                AdvanceDteDocumentPipelineJob::class
            );

        $method =
            $reflection->getMethod(
                'handle'
            );

        $lines =
            file(
                $method->getFileName()
            );

        $this->assertIsArray(
            $lines
        );

        $source =
            implode(
                '',
                array_slice(
                    $lines,
                    $method->getStartLine() - 1,
                    $method->getEndLine()
                        - $method->getStartLine()
                        + 1
                )
            );

        $compactSource =
            preg_replace(
                '/\s+/',
                '',
                $source
            );

        $this->assertIsString(
            $compactSource
        );

        /*
        |--------------------------------------------------------------------------
        | Debe existir un try alrededor del avance del pipeline
        |--------------------------------------------------------------------------
        */

        $this->assertStringContainsString(
            'try{',
            $compactSource
        );

        /*
        |--------------------------------------------------------------------------
        | Debe capturar SOLAMENTE la excepción de retry programado
        |--------------------------------------------------------------------------
        */

        $this->assertStringContainsString(
            'catch(DispatchRetryScheduledException$e){',
            $compactSource
        );

        /*
        |--------------------------------------------------------------------------
        | El catch controlado debe terminar el job
        |--------------------------------------------------------------------------
        |
        | No debe relanzar la excepción porque el retry de negocio
        | ya quedó persistido mediante retry_count / next_retry_at.
        |
        */

        $this->assertStringContainsString(
            'return;',
            $compactSource
        );

        /*
        |--------------------------------------------------------------------------
        | Protección crítica
        |--------------------------------------------------------------------------
        |
        | NO queremos un catch genérico de Throwable dentro de handle().
        |
        | Cualquier otra excepción debe seguir llegando a Laravel para
        | que funcionen tries() y backoff().
        |
        */

        $this->assertStringNotContainsString(
            'catch(Throwable',
            $compactSource
        );

        $this->assertStringNotContainsString(
            'catch(\\Throwable',
            $compactSource
        );
    }
}