<?php

namespace Tests\Unit;

use Tests\TestCase;

final class DocumentAutomationRetryConfigurationTest extends TestCase
{
    public function test_existe_una_politica_explicita_de_retry_para_automatizacion_del_documento(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Máximo de retries
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            3,
            (int) config(
                'dte.automation.document_retry.max_attempts'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Backoff de los retries
        |--------------------------------------------------------------------------
        |
        | Primer retry  -> 30 segundos
        | Segundo retry -> 120 segundos
        | Tercer retry  -> 300 segundos
        |
        */

        $this->assertSame(
            [
                30,
                120,
                300,
            ],
            array_values(
                (array) config(
                    'dte.automation.document_retry.backoff_seconds'
                )
            )
        );
    }
}