<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\Exceptions\DocumentAutomationNotRetryableException;
use RuntimeException;
use Tests\TestCase;

final class DocumentAutomationNotRetryableExceptionTest
    extends TestCase
{
    public function test_conserva_la_excepcion_original_y_las_referencias_del_fallo_no_retryable(): void
    {
        $previous =
            new RuntimeException(
                'El fallo no admite retry automático.'
            );

        $exception =
            new DocumentAutomationNotRetryableException(
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
            'El fallo no admite retry automático.',
            $exception->getMessage()
        );
    }
}
