<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\UseCases\Dispatch\PollSiiUploadStatusUseCase;
use App\Modules\Dte\Infrastructure\Sii\SiiSoapAuthenticationService;
use App\Modules\Dte\Infrastructure\Sii\SiiTokenProviderService;
use ReflectionClass;
use Tests\TestCase;

final class PollSiiUploadStatusUseCaseTest extends TestCase
{
    public function test_usa_el_proveedor_compartido_de_token_en_vez_de_autenticacion_directa(): void
    {
        $reflection = new ReflectionClass(
            PollSiiUploadStatusUseCase::class
        );

        $constructor = $reflection->getConstructor();

        $parameterTypes = array_map(
            static fn ($parameter) => $parameter->getType()?->getName(),
            $constructor?->getParameters() ?? []
        );

        $this->assertContains(
            SiiTokenProviderService::class,
            $parameterTypes
        );

        $this->assertNotContains(
            SiiSoapAuthenticationService::class,
            $parameterTypes
        );

        $source = file_get_contents(
            app_path(
                'Modules/Dte/Application/UseCases/Dispatch/PollSiiUploadStatusUseCase.php'
            )
        );

        $this->assertMatchesRegularExpression(
            '/\$this->siiTokenProviderService\s*->\s*get\s*\(/',
            $source
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\$this->siiSoapAuthenticationService\s*->\s*authenticate\s*\(/',
            $source
        );
    }
}
