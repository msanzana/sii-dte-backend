<?php

namespace Tests\Unit;

use Tests\TestCase;

final class AdvanceDteDocumentPipelineJobExhaustedRetryContractTest
    extends TestCase
{
    public function test_captura_el_agotamiento_del_retry_interno_sin_relanzarlo(): void
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

        /*
        |--------------------------------------------------------------------------
        | Import de la excepción terminal
        |--------------------------------------------------------------------------
        */

        $this->assertStringContainsString(
            'use App\Modules\Dte\Application\Exceptions\DocumentAutomationRetryExhaustedException;',
            $source
        );

        /*
        |--------------------------------------------------------------------------
        | Catch específico
        |--------------------------------------------------------------------------
        */

        $this->assertMatchesRegularExpression(
            '/catch\s*\(\s*DocumentAutomationRetryExhaustedException\s+\$e\s*\)/',
            $source
        );

        /*
        |--------------------------------------------------------------------------
        | Logging del documento
        |--------------------------------------------------------------------------
        |
        | Usamos regex porque el código productivo puede estar formateado como:
        |
        | 'document_id' => $e->documentId()
        |
        | o:
        |
        | 'document_id' =>
        |     $e->documentId()
        |
        */

        $this->assertMatchesRegularExpression(
            "/'document_id'\s*=>\s*\\\$e->documentId\(\)/",
            $source
        );

        /*
        |--------------------------------------------------------------------------
        | Logging de la acción
        |--------------------------------------------------------------------------
        */

        $this->assertMatchesRegularExpression(
            "/'action'\s*=>\s*\\\$e->action\(\)/",
            $source
        );

        /*
        |--------------------------------------------------------------------------
        | Debe registrar el agotamiento
        |--------------------------------------------------------------------------
        */

        $this->assertStringContainsString(
            'Retries internos de automatización DTE agotados',
            $source
        );

        /*
        |--------------------------------------------------------------------------
        | El catch debe terminar normalmente
        |--------------------------------------------------------------------------
        |
        | No queremos relanzar la excepción.
        |
        | El retry funcional ya quedó persistido como agotado, por lo que
        | Laravel no debe consumir tries() técnicos adicionales.
        |
        */

        $pattern =
            '/catch\s*\(\s*DocumentAutomationRetryExhaustedException\s+\$e\s*\)'
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