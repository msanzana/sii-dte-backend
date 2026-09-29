<?php

namespace Tests\Unit;

use Tests\TestCase;

final class AdvanceDteDocumentPipelineJobDocumentRetryContractTest
    extends TestCase
{
    public function test_captura_retry_interno_programado_sin_relanzarlo(): void
    {
        $path =
            app_path(
                'Jobs/Dte/Automation/AdvanceDteDocumentPipelineJob.php'
            );

        $this->assertFileExists(
            $path
        );

        $source =
            file_get_contents(
                $path
            );

        $this->assertIsString(
            $source
        );

        $this->assertStringContainsString(
            'use App\Modules\Dte\Application\Exceptions\DocumentAutomationRetryScheduledException;',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/catch\s*\(\s*DocumentAutomationRetryScheduledException\s+\$[A-Za-z_][A-Za-z0-9_]*\s*\)/',
            $source
        );
    }
}