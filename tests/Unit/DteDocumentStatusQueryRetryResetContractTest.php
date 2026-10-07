<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Entities\DteDocument;
use ReflectionMethod;
use Tests\TestCase;

final class DteDocumentStatusQueryRetryResetContractTest
    extends TestCase
{
    public function test_rebuild_xml_reinicia_explictamente_el_retry_de_consulta(): void
    {
        $method = new ReflectionMethod(
            DteDocument::class,
            'withUnsignedXmlBuilt'
        );

        $file = file(
            $method->getFileName()
        );

        $this->assertIsArray(
            $file
        );

        $source = implode(
            '',
            array_slice(
                $file,
                $method->getStartLine() - 1,
                $method->getEndLine()
                    - $method->getStartLine()
                    + 1
            )
        );

        $this->assertStringContainsString(
            'statusQueryRetryCount: 0',
            $source,
            'La reconstrucción XML debe iniciar un nuevo ciclo de consulta SII.'
        );

        $this->assertStringContainsString(
            'statusQueryNextAt: null',
            $source,
            'La reconstrucción XML debe eliminar la fecha del ciclo FAU anterior.'
        );
    }
}