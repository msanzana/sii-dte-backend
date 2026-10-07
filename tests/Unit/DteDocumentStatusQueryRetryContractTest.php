<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Entities\DteDocument;
use ReflectionClass;
use Tests\TestCase;

final class DteDocumentStatusQueryRetryContractTest extends TestCase
{
    public function test_constructor_expone_estado_de_retry_de_consulta_documental(): void
    {
        $reflection = new ReflectionClass(
            DteDocument::class
        );

        $constructor = $reflection->getConstructor();

        $this->assertNotNull(
            $constructor
        );

        $parameterNames = array_map(
            static fn ($parameter): string =>
                $parameter->getName(),
            $constructor->getParameters()
        );

        $this->assertContains(
            'statusQueryRetryCount',
            $parameterNames
        );

        $this->assertContains(
            'statusQueryNextAt',
            $parameterNames
        );
    }

    public function test_entidad_expone_getters_de_retry_de_consulta_documental(): void
    {
        $reflection = new ReflectionClass(
            DteDocument::class
        );

        $this->assertTrue(
            $reflection->hasMethod(
                'statusQueryRetryCount'
            )
        );

        $this->assertTrue(
            $reflection->hasMethod(
                'statusQueryNextAt'
            )
        );
    }

    public function test_entidad_expone_transiciones_de_retry_documental(): void
    {
        $reflection = new ReflectionClass(
            DteDocument::class
        );

        $this->assertTrue(
            $reflection->hasMethod(
                'withStatusQueryRetryScheduled'
            )
        );

        $this->assertTrue(
            $reflection->hasMethod(
                'withStatusQueryRetryExhausted'
            )
        );
    }
}