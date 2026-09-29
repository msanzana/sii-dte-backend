<?php

namespace Tests\Unit;

use Tests\TestCase;

class AdvanceDteAutomationUseCaseContractTest extends TestCase
{
    public function test_no_ignora_silenciosamente_una_accion_de_automatizacion_desconocida(): void
    {
        $source = file_get_contents(
            app_path(
                'Modules/Dte/Application/UseCases/Automation/AdvanceDteAutomationUseCase.php'
            )
        );

        $this->assertNotFalse($source);

        $this->assertStringNotContainsString(
            'default => null',
            $source,
            'AdvanceDteAutomationUseCase no debe ignorar silenciosamente acciones desconocidas.'
        );

        $this->assertMatchesRegularExpression(
            '/default\s*=>\s*throw/',
            $source,
            'Una acción de automatización desconocida debe generar una excepción explícita.'
        );
    }
}