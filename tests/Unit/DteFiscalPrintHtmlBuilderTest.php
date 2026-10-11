<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Enums\PrintCopyType;
use App\Modules\Dte\Domain\ValueObjects\DtePrintableCopy;
use App\Modules\Dte\Infrastructure\Pdf\Components\DteFiscalPrintHtmlBuilder;
use Tests\TestCase;

final class DteFiscalPrintHtmlBuilderTest extends TestCase
{
    public function test_renderiza_total_en_palabras(): void
    {
        $builder =
            new DteFiscalPrintHtmlBuilder();

        $html =
            $builder->renderTotalInWords(
                'CUARENTA Y SIETE MIL SEISCIENTOS PESOS'
            );

        $this->assertStringContainsString(
            'SON:',
            $html
        );

        $this->assertStringContainsString(
            'CUARENTA Y SIETE MIL SEISCIENTOS PESOS',
            $html
        );
    }

    public function test_total_en_palabras_vacio_no_renderiza_bloque(): void
    {
        $builder =
            new DteFiscalPrintHtmlBuilder();

        $this->assertSame(
            '',
            $builder->renderTotalInWords(
                null
            )
        );

        $this->assertSame(
            '',
            $builder->renderTotalInWords(
                '   '
            )
        );
    }

    public function test_original_no_renderiza_acuse_de_recibo(): void
    {
        $builder =
            new DteFiscalPrintHtmlBuilder();

        $copy =
            new DtePrintableCopy(
                type:
                    PrintCopyType::ORIGINAL,

                name:
                    'Original',

                quantity:
                    1,

                legend:
                    null,
            );

        $this->assertSame(
            '',
            $builder->renderAcknowledgement(
                $copy
            )
        );
    }

    public function test_cliente_no_renderiza_acuse_de_recibo(): void
    {
        $builder =
            new DteFiscalPrintHtmlBuilder();

        $copy =
            new DtePrintableCopy(
                type:
                    PrintCopyType::CLIENT,

                name:
                    'Copia Cliente',

                quantity:
                    1,

                legend:
                    'COPIA CLIENTE',
            );

        $this->assertSame(
            '',
            $builder->renderAcknowledgement(
                $copy
            )
        );
    }

    public function test_cedible_renderiza_acuse_completo(): void
    {
        $builder =
            new DteFiscalPrintHtmlBuilder();

        $copy =
            new DtePrintableCopy(
                type:
                    PrintCopyType::CEDIBLE,

                name:
                    'Cedible',

                quantity:
                    1,

                legend:
                    'CEDIBLE',
            );

        $html =
            $builder->renderAcknowledgement(
                $copy
            );

        $this->assertStringContainsString(
            'ACUSE DE RECIBO',
            $html
        );

        $this->assertStringContainsString(
            'Nombre:',
            $html
        );

        $this->assertStringContainsString(
            'RUT:',
            $html
        );

        $this->assertStringContainsString(
            'Fecha:',
            $html
        );

        $this->assertStringContainsString(
            'Recinto:',
            $html
        );

        $this->assertStringContainsString(
            'Firma:',
            $html
        );

        $this->assertStringContainsString(
            'Ley 19.983',
            $html
        );
    }

    public function test_total_en_palabras_escapa_html(): void
    {
        $builder =
            new DteFiscalPrintHtmlBuilder();

        $html =
            $builder->renderTotalInWords(
                '<script>alert("x")</script>'
            );

        $this->assertStringNotContainsString(
            '<script>',
            $html
        );

        $this->assertStringContainsString(
            '&lt;script&gt;',
            $html
        );
    }
}