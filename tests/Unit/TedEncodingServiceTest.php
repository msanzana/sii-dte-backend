<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Exceptions\InvalidTedDataException;
use App\Modules\Dte\Domain\Services\TedEncodingService;
use App\Modules\Dte\Infrastructure\Xml\DteTedEmbedderService;
use App\Modules\Dte\Infrastructure\Xml\TedSignatureService;
use Tests\TestCase;

final class TedEncodingServiceTest extends TestCase
{
    public function test_convierte_texto_latin1_sin_alterarlo(): void
    {
        $original =
            '<TED><RSR>José Ñandú</RSR></TED>';

        $latin1 =
            TedEncodingService::utf8ToLatin1Strict(
                $original,
                'TED de prueba'
            );

        $roundTrip =
            TedEncodingService::latin1ToUtf8Strict(
                $latin1,
                'TED de prueba'
            );

        $this->assertSame(
            $original,
            $roundTrip
        );

        $this->assertNotSame(
            $original,
            $latin1
        );
    }

    public function test_normaliza_espacios_sin_perder_caracteres_latin1(): void
    {
        $normalized =
            TedEncodingService::normalizeUtf8Text(
                "  José   Ñandú \n Comercial  "
            );

        $this->assertSame(
            'José Ñandú Comercial',
            $normalized
        );
    }

    public function test_rechaza_caracter_no_representable_en_latin1(): void
    {
        $this->expectException(
            InvalidTedDataException::class
        );

        $this->expectExceptionMessage(
            'ISO-8859-1'
        );

        TedEncodingService::utf8ToLatin1Strict(
            'Cliente 😀',
            'texto del TED'
        );
    }

    public function test_embedder_preserva_exactamente_caracteres_latin1(): void
    {
        $dteLatin1 =
            '<?xml version="1.0" encoding="ISO-8859-1"?>'
            . '<DTE><Documento>'
            . '<TmstFirma>2026-10-09T01:00:00</TmstFirma>'
            . '</Documento></DTE>';

        $tedUtf8 =
            '<TED version="1.0">'
            . '<DD><RSR>José Ñandú</RSR></DD>'
            . '</TED>';

        $embeddedLatin1 =
            (new DteTedEmbedderService())
                ->embedTed(
                    $dteLatin1,
                    $tedUtf8
                );

        $embeddedUtf8 =
            TedEncodingService::latin1ToUtf8Strict(
                $embeddedLatin1,
                'DTE con TED'
            );

        $this->assertStringContainsString(
            $tedUtf8,
            $embeddedUtf8
        );
    }

    public function test_embedder_rechaza_ted_con_caracter_no_latin1(): void
    {
        $dteLatin1 =
            '<DTE><Documento>'
            . '<TmstFirma>2026-10-09T01:00:00</TmstFirma>'
            . '</Documento></DTE>';

        $this->expectException(
            InvalidTedDataException::class
        );

        $this->expectExceptionMessage(
            'ISO-8859-1'
        );

        (new DteTedEmbedderService())
            ->embedTed(
                $dteLatin1,
                '<TED><DD><RSR>Cliente 😀</RSR></DD></TED>'
            );
    }

    public function test_firma_rechaza_dd_con_caracter_no_latin1_antes_de_openssl(): void
    {
        $this->expectException(
            InvalidTedDataException::class
        );

        $this->expectExceptionMessage(
            'ISO-8859-1'
        );

        (new TedSignatureService())
            ->singDdXml(
                '<DD><RSR>Cliente 😀</RSR></DD>',
                'ESTA_LLAVE_NO_DEBE_LLEGAR_A_USARSE'
            );
    }
}