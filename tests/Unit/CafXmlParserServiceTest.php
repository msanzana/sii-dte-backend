<?php

namespace Tests\Unit;

use App\Modules\Dte\Infrastructure\Xml\CafXmlParserService;
use Tests\TestCase;

final class CafXmlParserServiceTest extends TestCase
{
    public function test_extrae_el_rut_emisor_desde_el_nodo_re_del_caf(): void
    {
        $xml = <<<'XML'
<AUTORIZACION>
    <CAF version="1.0">
        <DA>
            <RE>76123456-7</RE>
            <TD>39</TD>

            <RNG>
                <D>1</D>
                <H>10</H>
            </RNG>

            <FA>2026-09-17</FA>

            <RSAPK>
                <M>MODULO-PRUEBA</M>
                <E>Aw==</E>
            </RSAPK>

            <IDK>100</IDK>
        </DA>

        <FRMA algoritmo="SHA1withRSA">
            FIRMA-BASE64-PRUEBA
        </FRMA>
    </CAF>

    <RSASK>CLAVE-PRUEBA</RSASK>
</AUTORIZACION>
XML;

        $service = new CafXmlParserService();

        $parsed = $service->parse($xml);

        $this->assertArrayHasKey(
            'issuer_rut',
            $parsed
        );

        $this->assertSame(
            '76123456-7',
            $parsed['issuer_rut']
        );
    }
    public function test_extrae_idk_algoritmo_y_firma_frma_del_caf(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<AUTORIZACION>
    <CAF version="1.0">
        <DA>
            <RE>76123456-7</RE>
            <TD>39</TD>
            <RNG>
                <D>1</D>
                <H>10</H>
            </RNG>
            <FA>2026-09-17</FA>
            <RSAPK>
                <M>MODULO-PRUEBA</M>
                <E>Aw==</E>
            </RSAPK>
            <IDK>100</IDK>
        </DA>
        <FRMA algoritmo="SHA1withRSA">FIRMA-BASE64-PRUEBA</FRMA>
    </CAF>
    <RSASK>CLAVE-PRUEBA</RSASK>
</AUTORIZACION>
XML;

        $service = new CafXmlParserService();

        $parsed = $service->parse($xml);

        $this->assertSame(
            '100',
            $parsed['sii_key_id']
        );

        $this->assertSame(
            'SHA1withRSA',
            $parsed['frma_algorithm']
        );

        $this->assertSame(
            'FIRMA-BASE64-PRUEBA',
            $parsed['frma_value']
        );
    }
    public function test_extrae_la_llave_publica_pem_desde_rsapubk(): void
    {
    $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<AUTORIZACION>
    <CAF version="1.0">
        <DA>
            <RE>76123456-7</RE>
            <TD>39</TD>
            <RNG>
                <D>1</D>
                <H>10</H>
            </RNG>
            <FA>2026-09-17</FA>
            <RSAPK>
                <M>MODULO-PRUEBA</M>
                <E>Aw==</E>
            </RSAPK>
            <IDK>100</IDK>
        </DA>

        <FRMA algoritmo="SHA1withRSA">
            FIRMA-BASE64-PRUEBA
        </FRMA>
    </CAF>

    <RSASK>CLAVE-PRUEBA</RSASK>

    <RSAPUBK>-----BEGIN PUBLIC KEY-----
LLAVE-PUBLICA-PRUEBA
-----END PUBLIC KEY-----</RSAPUBK>
</AUTORIZACION>
XML;

        $service = new CafXmlParserService();

        $parsed = $service->parse($xml);

        $this->assertArrayHasKey(
            'public_key_pem',
            $parsed
        );

        $this->assertSame(
            "-----BEGIN PUBLIC KEY-----\n"
            . "LLAVE-PUBLICA-PRUEBA\n"
            . "-----END PUBLIC KEY-----",
            $parsed['public_key_pem']
        );
    }
    public function test_conserva_el_fragmento_da_exacto_para_verificar_frma(): void
    {
    $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<AUTORIZACION>
    <CAF version="1.0">
        <DA>
            <RE>76123456-7</RE>
            <TD>39</TD>
            <RNG>
                <D>1</D>
                <H>10</H>
            </RNG>
            <FA>2026-09-17</FA>
            <RSAPK>
                <M>MODULO-PRUEBA</M>
                <E>Aw==</E>
            </RSAPK>
            <IDK>100</IDK>
        </DA>
        <FRMA algoritmo="SHA1withRSA">FIRMA-BASE64-PRUEBA</FRMA>
    </CAF>

    <RSASK>CLAVE-PRUEBA</RSASK>

    <RSAPUBK>-----BEGIN PUBLIC KEY-----
LLAVE-PUBLICA-PRUEBA
-----END PUBLIC KEY-----</RSAPUBK>
</AUTORIZACION>
XML;

        $expectedDa =
            "<DA>\n"
            . "            <RE>76123456-7</RE>\n"
            . "            <TD>39</TD>\n"
            . "            <RNG>\n"
            . "                <D>1</D>\n"
            . "                <H>10</H>\n"
            . "            </RNG>\n"
            . "            <FA>2026-09-17</FA>\n"
            . "            <RSAPK>\n"
            . "                <M>MODULO-PRUEBA</M>\n"
            . "                <E>Aw==</E>\n"
            . "            </RSAPK>\n"
            . "            <IDK>100</IDK>\n"
            . "        </DA>";

        $service = new CafXmlParserService();

        $parsed = $service->parse($xml);

        $this->assertArrayHasKey(
            'da_xml',
            $parsed
        );

        $this->assertSame(
            $expectedDa,
            $parsed['da_xml']
        );
    }
}
