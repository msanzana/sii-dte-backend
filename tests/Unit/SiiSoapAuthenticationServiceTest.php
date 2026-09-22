<?php

namespace Tests\Unit;

use App\Modules\Dte\Infrastructure\Sii\SiiSeedXmlSignerService;
use App\Modules\Dte\Infrastructure\Sii\SiiSoapAuthenticationService;
use DOMDocument;
use DOMXPath;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

final class SiiSoapAuthenticationServiceTest extends TestCase
{
    public function test_la_autenticacion_soap_delega_la_firma_al_firmador_compartido(): void
    {
        $reflection = new ReflectionClass(
            SiiSoapAuthenticationService::class
        );

        $constructor = $reflection->getConstructor();

        $this->assertNotNull(
            $constructor
        );

        $parameterTypes = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof \ReflectionNamedType) {
                $parameterTypes[] = $type->getName();
            }
        }

        $this->assertContains(
            SiiSeedXmlSignerService::class,
            $parameterTypes,
            'SiiSoapAuthenticationService debe recibir SiiSeedXmlSignerService por inyección de dependencias.'
        );

        $source = file_get_contents(
            app_path(
                'Modules/Dte/Infrastructure/Sii/SiiSoapAuthenticationService.php'
            )
        );

        $this->assertMatchesRegularExpression(
            '/\$signedSeedXml\s*=\s*\$this->seedXmlSignerService->sign\s*\(/s',
            $source
        );
    }
}