<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Entities\Company;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use App\Modules\Dte\Infrastructure\Xml\EnvioBoletaEnvelopeBuilderService;
use DOMDocument;
use Tests\TestCase;

class EnvioBoletaEnvelopeBuilderServiceTest extends TestCase
{
    public function test_envio_boleta_declara_schema_location_oficial_del_sii(): void
    {
        $source = file_get_contents(
            app_path(
                'Modules/Dte/Infrastructure/Xml/EnvioBoletaEnvelopeBuilderService.php'
            )
        );

        $this->assertIsString(
            $source
        );

        $this->assertStringContainsString(
            "'http://www.sii.cl/SiiDte EnvioBOLETA_v11.xsd'",
            $source
        );

        $this->assertStringContainsString(
            "'xsi:schemaLocation'",
            $source
        );

        $this->assertStringContainsString(
            'self::ENVIO_BOLETA_SCHEMA_LOCATION',
            $source
        );
    }
    public function test_envio_boleta_incorpora_el_dte_firmado_sin_importnode(): void
    {
        $source = file_get_contents(
            app_path(
                'Modules/Dte/Infrastructure/Xml/EnvioBoletaEnvelopeBuilderService.php'
            )
        );

        $this->assertIsString(
            $source
        );

        $this->assertStringNotContainsString(
            '->importNode(',
            $source
        );

        $this->assertStringContainsString(
            'SIGNED_DTE_XML_PLACEHOLDER',
            $source
        );

        $this->assertStringContainsString(
            'preg_replace(',
            $source
        );

        $this->assertStringContainsString(
            'str_replace(',
            $source
        );

        $this->assertStringContainsString(
            'assertAllSignaturesValid(',
            $source
        );
    }
}