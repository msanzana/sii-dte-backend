<?php

namespace Tests\Unit;

use Tests\TestCase;

final class DteDocumentStatusQueryRetryConfigTest
    extends TestCase
{
    public function test_configura_retry_de_consulta_documental(): void
    {
        $this->assertSame(
            3,
            config(
                'dte.automation.document_status_retry.max_attempts'
            )
        );

        $this->assertSame(
            [
                60,
                180,
                600,
            ],
            config(
                'dte.automation.document_status_retry.backoff_seconds'
            )
        );
    }
}