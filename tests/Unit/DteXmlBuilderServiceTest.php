<?php

namespace Tests\Unit;

use App\Modules\Dte\Infrastructure\Xml\DteXmlBuilderService;
use DOMDocument;
use Tests\TestCase;

class DteXmlBuilderServiceTest extends TestCase
{
    public function test_boleta_genera_ind_servicio_despues_de_fch_emis(): void
    {
        $service = new DteXmlBuilderService();

        $xml = $service->build([
            'version' => '1.0',
            'document_xml_id' => 'DTE_39_1',

            'id_doc' => [
                'tipo_dte' => '39',
                'folio' => '1',
                'fecha_emision' => '2026-09-18',
                'ind_servicio' => '3',
            ],

            'emitter' => [
                'rut' => '77760724-3',
                'razon_social' => 'Empresa de prueba',
                'giro' => 'COMERCIO PRODUCTOS',
                'email' => 'empresa@prueba.cl',
                'acteco' => '479100',
                'direccion' => 'Dirección prueba',
                'commune' => 'Temuco',
                'city' => 'Temuco',
            ],

            'receiver' => [
                'rut' => '11111111-1',
                'razon_social' => 'Cliente prueba',
                'giro' => null,
                'direccion' => null,
                'commune' => null,
                'city' => null,
            ],

            'totals' => [
                'net_amount' => '1000',
                'exempt_amount' => null,
                'tax_rate' => '19',
                'tax_amount' => '190',
                'total_amount' => '1190',
            ],

            'details' => [],

            'references' => [],
        ]);

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $idDoc = $dom->getElementsByTagName('IdDoc')->item(0);

        $this->assertNotNull($idDoc);

        $elements = [];

        foreach ($idDoc->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $elements[$child->localName] = $child->textContent;
            }
        }

        $this->assertArrayHasKey(
            'IndServicio',
            $elements
        );

        $this->assertSame(
            '3',
            $elements['IndServicio']
        );

        $this->assertSame(
            [
                'TipoDTE',
                'Folio',
                'FchEmis',
                'IndServicio',
            ],
            array_keys($elements)
        );
    }
    public function test_boleta_39_usa_nodos_propios_del_schema_de_boleta(): void
    {
        $service = new DteXmlBuilderService();

        $xml = $service->build([
            'version' => '1.0',
            'document_xml_id' => 'DTE_39_SCHEMA_TEST',

            'id_doc' => [
                'tipo_dte' => '39',
                'folio' => '1',
                'fecha_emision' => '2026-09-21',
                'ind_servicio' => '3',
            ],

            'emitter' => [
                'rut' => '77760724-3',
                'razon_social' => 'Empresa de prueba',
                'giro' => 'COMERCIO PRODUCTOS',
                'email' => 'empresa@prueba.cl',
                'acteco' => '479100',
                'direccion' => 'Direccion prueba',
                'commune' => 'Temuco',
                'city' => 'Temuco',
            ],

            'receiver' => [
                'rut' => '11111111-1',
                'razon_social' => 'Cliente prueba',
                'giro' => 'SERVICIOS',
                'direccion' => 'Direccion receptor',
                'commune' => 'Temuco',
                'city' => 'Temuco',
            ],

            'totals' => [
                'net_amount' => '1000',
                'exempt_amount' => null,
                'tax_rate' => '19',
                'tax_amount' => '190',
                'total_amount' => '1190',
            ],

            'details' => [],
            'references' => [],
        ]);

        $dom = new DOMDocument();

        $this->assertTrue(
            $dom->loadXML($xml)
        );

        /*
        |--------------------------------------------------------------------------
        | Emisor de Boleta
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            1,
            $dom->getElementsByTagName('RznSocEmisor')->length
        );

        $this->assertSame(
            1,
            $dom->getElementsByTagName('GiroEmisor')->length
        );

        /*
        |--------------------------------------------------------------------------
        | Nombres usados por Factura no deben aparecer en Boleta
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            0,
            $dom->getElementsByTagName('RznSoc')->length
        );

        $this->assertSame(
            0,
            $dom->getElementsByTagName('GiroEmis')->length
        );

        /*
        |--------------------------------------------------------------------------
        | Campos no permitidos por el schema de Boleta
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            0,
            $dom->getElementsByTagName('GiroRecep')->length
        );

        $this->assertSame(
            0,
            $dom->getElementsByTagName('TasaIVA')->length
        );

        $this->assertSame(
            0,
            $dom->getElementsByTagName('CorreoEmisor')->length
        );

        $this->assertSame(
            0,
            $dom->getElementsByTagName('Acteco')->length
        );

        /*
        |--------------------------------------------------------------------------
        | IVA sí debe mantenerse para Boleta 39 afecta
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            1,
            $dom->getElementsByTagName('IVA')->length
        );
    }

    public function test_factura_33_conserva_sus_nodos_actuales(): void
    {
        $service = new DteXmlBuilderService();

        $xml = $service->build([
            'version' => '1.0',
            'document_xml_id' => 'DTE_33_SCHEMA_TEST',

            'id_doc' => [
                'tipo_dte' => '33',
                'folio' => '100',
                'fecha_emision' => '2026-09-21',
            ],

            'emitter' => [
                'rut' => '77760724-3',
                'razon_social' => 'Empresa de prueba',
                'giro' => 'COMERCIO PRODUCTOS',
                'email' => 'empresa@prueba.cl',
                'acteco' => '479100',
                'direccion' => 'Direccion prueba',
                'commune' => 'Temuco',
                'city' => 'Temuco',
            ],

            'receiver' => [
                'rut' => '11111111-1',
                'razon_social' => 'Cliente factura',
                'giro' => 'SERVICIOS',
                'direccion' => 'Direccion receptor',
                'commune' => 'Temuco',
                'city' => 'Temuco',
            ],

            'totals' => [
                'net_amount' => '1000',
                'exempt_amount' => null,
                'tax_rate' => '19',
                'tax_amount' => '190',
                'total_amount' => '1190',
            ],

            'details' => [],
            'references' => [],
        ]);

        $dom = new DOMDocument();

        $this->assertTrue(
            $dom->loadXML($xml)
        );

        /*
        |--------------------------------------------------------------------------
        | Factura conserva su estructura actual
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            1,
            $dom->getElementsByTagName('RznSoc')->length
        );

        $this->assertSame(
            1,
            $dom->getElementsByTagName('GiroEmis')->length
        );

        $this->assertSame(
            1,
            $dom->getElementsByTagName('GiroRecep')->length
        );

        $this->assertSame(
            1,
            $dom->getElementsByTagName('TasaIVA')->length
        );

        /*
        |--------------------------------------------------------------------------
        | Factura no debe recibir nombres exclusivos de Boleta
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            0,
            $dom->getElementsByTagName('RznSocEmisor')->length
        );

        $this->assertSame(
            0,
            $dom->getElementsByTagName('GiroEmisor')->length
        );
    }
    public function test_boleta_39_genera_ind_mnt_neto_despues_de_ind_servicio(): void
    {
        $service = new DteXmlBuilderService();

        $xml = $service->build([
            'version' => '1.0',
            'document_xml_id' => 'DTE_39_NETO_TEST',

            'id_doc' => [
                'tipo_dte' => '39',
                'folio' => '1',
                'fecha_emision' => '2026-09-22',
                'ind_servicio' => '3',
                'ind_mnt_neto' => '2',
            ],

            'emitter' => [
                'rut' => '77760724-3',
                'razon_social' => 'Empresa de prueba',
                'giro' => 'COMERCIO PRODUCTOS',
                'email' => 'empresa@prueba.cl',
                'acteco' => '479100',
                'direccion' => 'Direccion prueba',
                'commune' => 'Temuco',
                'city' => 'Temuco',
            ],

            'receiver' => [
                'rut' => '11111111-1',
                'razon_social' => 'Cliente prueba',
                'giro' => null,
                'direccion' => null,
                'commune' => null,
                'city' => null,
            ],

            'totals' => [
                'net_amount' => '40000',
                'exempt_amount' => null,
                'tax_rate' => '19',
                'tax_amount' => '7600',
                'total_amount' => '47600',
            ],

            'details' => [],
            'references' => [],
        ]);

        $dom = new DOMDocument();
        $this->assertTrue($dom->loadXML($xml));

        $idDoc = $dom->getElementsByTagName('IdDoc')->item(0);

        $this->assertNotNull($idDoc);

        $elements = [];

        foreach ($idDoc->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $elements[$child->localName] = $child->textContent;
            }
        }

        $this->assertSame(
            '2',
            $elements['IndMntNeto'] ?? null
        );

        $this->assertSame(
            [
                'TipoDTE',
                'Folio',
                'FchEmis',
                'IndServicio',
                'IndMntNeto',
            ],
            array_keys($elements)
        );
    }
}