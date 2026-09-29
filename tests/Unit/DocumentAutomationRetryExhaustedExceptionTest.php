<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\Exceptions\DocumentAutomationRetryExhaustedException;
use RuntimeException;
use Tests\TestCase;

final class DocumentAutomationRetryExhaustedExceptionTest
    extends TestCase
{
    public function test_conserva_la_excepcion_original_y_las_referencias_del_retry_agotado(): void
    {
        $previous =
            new RuntimeException(
                'Falló el último intento de build_xml.'
            );

        $exception =
            new DocumentAutomationRetryExhaustedException(
                documentId:
                    77,

                action:
                    'build_xml',

                previous:
                    $previous
            );

        $this->assertSame(
            77,
            $exception->documentId()
        );

        $this->assertSame(
            'build_xml',
            $exception->action()
        );

        $this->assertSame(
            $previous,
            $exception->getPrevious()
        );

        $this->assertSame(
            'Falló el último intento de build_xml.',
            $exception->getMessage()
        );
    }
}