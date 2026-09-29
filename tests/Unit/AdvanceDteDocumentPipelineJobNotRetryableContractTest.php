<?php

namespace Tests\Unit;

use Tests\TestCase;

final class AdvanceDteDocumentPipelineJobNotRetryableContractTest
    extends TestCase
{
    public function test_captura_fallo_no_retryable_sin_relanzarlo(): void
    {
        $source =
            file_get_contents(
                app_path(
                    'Jobs/Dte/Automation/AdvanceDteDocumentPipelineJob.php'
                )
            );

        $this->assertNotFalse(
            $source
        );

        $this->assertStringContainsString(
            'use App\Modules\Dte\Application\Exceptions\DocumentAutomationNotRetryableException;',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/catch\s*\(\s*DocumentAutomationNotRetryableException\s+\$e\s*\)/',
            $source
        );

        $this->assertMatchesRegularExpression(
            "/'document_id'\s*=>\s*\\\$e->documentId\(\)/",
            $source
        );

        $this->assertMatchesRegularExpression(
            "/'action'\s*=>\s*\\\$e->action\(\)/",
            $source
        );

        $this->assertStringContainsString(
            'no admite retry automático',
            $source
        );

        /*
        |--------------------------------------------------------------------------
        | Debe terminar normalmente
        |--------------------------------------------------------------------------
        |
        | El fallo determinístico ya quedó persistido como NOT_RETRYABLE.
        | No corresponde consumir tries/backoff técnicos de Laravel.
        |
        */

        $pattern =
            '/catch\s*\(\s*DocumentAutomationNotRetryableException\s+\$e\s*\)'
            . '\s*\{'
            . '.*?'
            . '\breturn\s*;'
            . '.*?'
            . '\}/s';

        $this->assertMatchesRegularExpression(
            $pattern,
            $source
        );
    }
}