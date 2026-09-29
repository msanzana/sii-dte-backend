<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\Exceptions\DocumentAutomationRetryScheduledException;
use RuntimeException;
use Tests\TestCase;

final class DocumentAutomationRetryScheduledExceptionTest extends TestCase
{
    public function test_conserva_la_excepcion_original_y_las_referencias_del_retry(): void
    {
        $previous =
            new RuntimeException(
                'Falló la generación del XML.'
            );

        $exception =
            new DocumentAutomationRetryScheduledException(
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
            'Falló la generación del XML.',
            $exception->getMessage()
        );
    }
}