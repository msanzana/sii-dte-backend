<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Exceptions\DispatchRetryScheduledException;
use RuntimeException;
use Tests\TestCase;

final class DispatchRetryScheduledExceptionTest extends TestCase
{
    public function test_conserva_la_excepcion_original_y_las_referencias_del_retry(): void
    {
        $previous =
            new RuntimeException(
                'Falló la obtención del TOKEN.'
            );

        $exception =
            DispatchRetryScheduledException::forScheduledRetry(
                dispatchId: 25,
                documentId: 100,
                previous: $previous
            );

        $this->assertSame(
            25,
            $exception->dispatchId()
        );

        $this->assertSame(
            100,
            $exception->documentId()
        );

        $this->assertSame(
            $previous,
            $exception->getPrevious()
        );

        $this->assertSame(
            $previous->getMessage(),
            $exception->getMessage()
        );
    }
}