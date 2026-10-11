<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Exceptions\InvalidTedDataException;
use App\Modules\Dte\Domain\Services\TedEncodingService;
use App\Modules\Dte\Infrastructure\Barcode\LePdf417Renderer;
use Tests\TestCase;

final class LePdf417RendererTest extends TestCase
{
    public function test_genera_pdf417_svg_con_parametros_requeridos(): void
    {
        $result =
            (new LePdf417Renderer())
                ->render(
                    $this->ted(),
                    15
                );

        $this->assertSame(
            'image/svg+xml',
            $result->mimeType()
        );

        $this->assertSame(
            15,
            $result->columns()
        );

        $this->assertSame(
            5,
            $result->securityLevel()
        );

        $this->assertSame(
            3,
            $result->moduleAspectRatio()
        );

        $this->assertContains(
            $result->compactionCodeword(),
            [901, 924]
        );

        $this->assertGreaterThan(
            0,
            $result->rows()
        );

        $this->assertStringContainsString(
            '<svg',
            $result->svg()
        );

        $this->assertStringContainsString(
            'id="barcode"',
            $result->svg()
        );

        $this->assertStringContainsString(
            'fill="#000000"',
            $result->svg()
        );
    }

    public function test_pdf417_codifica_los_bytes_latin1_del_ted(): void
    {
        $ted =
            '<TED version="1.0">'
            . '<DD>'
            . '<RSR>José Ñandú</RSR>'
            . '</DD>'
            . '<FRMT algoritmo="SHA1withRSA">'
            . 'QUJD'
            . '</FRMT>'
            . '</TED>';

        $latin1 =
            TedEncodingService::utf8ToLatin1Strict(
                $ted,
                'TED de prueba'
            );

        $result =
            (new LePdf417Renderer())
                ->render(
                    $ted,
                    15
                );

        $this->assertSame(
            strlen($latin1),
            $result->payloadByteLength()
        );

        $this->assertSame(
            hash(
                'sha256',
                $latin1
            ),
            $result->payloadSha256()
        );
    }

    public function test_rechaza_ted_con_caracter_no_latin1(): void
    {
        $this->expectException(
            InvalidTedDataException::class
        );

        $this->expectExceptionMessage(
            'ISO-8859-1'
        );

        (new LePdf417Renderer())
            ->render(
                '<TED><DD><RSR>Cliente 😀</RSR></DD></TED>',
                15
            );
    }

    public function test_rechaza_cantidad_de_columnas_fuera_de_rango(): void
    {
        $this->expectException(
            \InvalidArgumentException::class
        );

        (new LePdf417Renderer())
            ->render(
                $this->ted(),
                0
            );
    }

    public function test_rechaza_ted_que_excede_capacidad_del_pdf417(): void
    {
        $ted =
            '<TED><DD><RSR>'
            . str_repeat(
                'A',
                3000
            )
            . '</RSR></DD></TED>';

        $this->expectException(
            InvalidTedDataException::class
        );

        $this->expectExceptionMessage(
            'excede'
        );

        (new LePdf417Renderer())
            ->render(
                $ted,
                15
            );
    }

    private function ted(): string
    {
        return
            '<TED version="1.0">'
            . '<DD>'
            . '<RE>77760724-3</RE>'
            . '<TD>33</TD>'
            . '<F>100</F>'
            . '<FE>2026-10-09</FE>'
            . '<RR>11111111-1</RR>'
            . '<RSR>Cliente Prueba</RSR>'
            . '<MNT>1190</MNT>'
            . '<IT1>Producto Prueba</IT1>'
            . '<CAF version="1.0">'
            . '<DA>'
            . '<RE>77760724-3</RE>'
            . '<TD>33</TD>'
            . '<RNG><D>1</D><H>100</H></RNG>'
            . '<FA>2026-01-01</FA>'
            . '<RSAPK>'
            . '<M>QUJD</M>'
            . '<E>AQAB</E>'
            . '</RSAPK>'
            . '<IDK>100</IDK>'
            . '</DA>'
            . '<FRMA algoritmo="SHA1withRSA">'
            . 'QUJD'
            . '</FRMA>'
            . '</CAF>'
            . '<TSTED>2026-10-09T02:00:00</TSTED>'
            . '</DD>'
            . '<FRMT algoritmo="SHA1withRSA">'
            . 'QUJD'
            . '</FRMT>'
            . '</TED>';
    }
}