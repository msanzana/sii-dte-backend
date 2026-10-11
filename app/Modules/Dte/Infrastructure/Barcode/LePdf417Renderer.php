<?php

namespace App\Modules\Dte\Infrastructure\Barcode;

use App\Modules\Dte\Domain\Exceptions\InvalidTedDataException;
use App\Modules\Dte\Domain\RendererContracts\DtePdf417RendererInterface;
use App\Modules\Dte\Domain\Services\TedEncodingService;
use App\Modules\Dte\Domain\ValueObjects\DtePdf417RenderResult;
use Le\PDF417\PDF417;
use Le\PDF417\Renderer\SvgRenderer;

final class LePdf417Renderer implements DtePdf417RendererInterface
{
    private const ERROR_CORRECTION_LEVEL = 5;

    private const MODULE_ASPECT_RATIO = 3;

    private const BYTE_COMPACTION_CODEWORDS = [
        901,
        924,
    ];

    public function render(
        string $tedXmlUtf8,
        int $columns = 15
    ): DtePdf417RenderResult {
        $this->assertValidColumns(
            $columns
        );

        /*
        |--------------------------------------------------------------------------
        | El contenido codificado es el TED persistido
        |--------------------------------------------------------------------------
        |
        | No reconstruimos el TED.
        | No parseamos y reserializamos el XML.
        | No cambiamos espacios, tags ni entidades.
        |
        | Únicamente hacemos la conversión estricta de UTF-8 a ISO-8859-1
        | que ya utiliza el resto de la cadena TED.
        |
        */

        $tedLatin1 =
            TedEncodingService::utf8ToLatin1Strict(
                $tedXmlUtf8,
                'TED persistido para PDF417'
            );

        $pdf417 = new PDF417();

        $pdf417->setColumns(
            $columns
        );

        $pdf417->setSecurityLevel(
            self::ERROR_CORRECTION_LEVEL
        );

        $pdf417->setForceBinary(
            true
        );

        /*
        |--------------------------------------------------------------------------
        | Inspección previa de los codewords
        |--------------------------------------------------------------------------
        |
        | encodeData() agrega primero el descriptor de longitud.
        | Por eso el segundo codeword debe ser 901 o 924 cuando el encoder
        | realmente está trabajando en Byte Compaction Mode.
        |
        */

        $codewords =
            $pdf417->encodeData(
                $tedLatin1
            );

        $codewordCount =
            count($codewords);

        if (
            $codewordCount
            > PDF417::MAX_CODE_WORDS
        ) {
            throw InvalidTedDataException::because(
                sprintf(
                    'El TED requiere %d codewords y excede la capacidad admitida del PDF417.',
                    $codewordCount
                )
            );
        }

        if (
            $codewordCount % $columns !== 0
        ) {
            throw InvalidTedDataException::because(
                'La matriz de codewords del PDF417 no quedó alineada con la cantidad de columnas.'
            );
        }

        $rows =
            intdiv(
                $codewordCount,
                $columns
            );

        if ($rows > PDF417::MAX_ROWS) {
            throw InvalidTedDataException::because(
                sprintf(
                    'El TED requiere %d filas y excede el máximo admitido por PDF417.',
                    $rows
                )
            );
        }

        $compactionCodeword =
            $codewords[1] ?? null;

        if (
            !is_int($compactionCodeword)
            || !in_array(
                $compactionCodeword,
                self::BYTE_COMPACTION_CODEWORDS,
                true
            )
        ) {
            throw InvalidTedDataException::because(
                'El encoder PDF417 no utilizó Byte Compaction Mode para el TED.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Construcción del símbolo completo
        |--------------------------------------------------------------------------
        */

        $barcodeData =
            $pdf417->encode(
                $tedLatin1
            );

        if (
            $barcodeData->securityLevel
            !== self::ERROR_CORRECTION_LEVEL
        ) {
            throw InvalidTedDataException::because(
                'El PDF417 no quedó generado con Error Correction Level 5.'
            );
        }

        if (
            $barcodeData->columns
            !== $columns
        ) {
            throw InvalidTedDataException::because(
                'La cantidad final de columnas del PDF417 no coincide con la solicitada.'
            );
        }

        if (
            $barcodeData->rows
            !== $rows
        ) {
            throw InvalidTedDataException::because(
                'La cantidad final de filas del PDF417 no coincide con la matriz calculada.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Comprobar que no sea PDF417 truncado
        |--------------------------------------------------------------------------
        |
        | La implementación completa genera por fila:
        |
        | START
        | + indicador izquierdo
        | + columnas de datos
        | + indicador derecho
        | + STOP
        |
        | Es decir: columns + 4 elementos.
        |
        */

        foreach ($barcodeData->codes as $row) {
            if (
                count($row)
                !== $columns + 4
            ) {
                throw InvalidTedDataException::because(
                    'El encoder no produjo la estructura completa esperada de PDF417.'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SVG en unidades de módulo
        |--------------------------------------------------------------------------
        |
        | scale=1 permite que el layout físico posterior decida los milímetros
        | reales sin degradar el código.
        |
        | ratio=3 mantiene cada fila con altura equivalente a 3 módulos X.
        |
        */

        $renderer =
            new SvgRenderer([
                'scale' => 1,
                'ratio' =>
                    self::MODULE_ASPECT_RATIO,
                'color' => '#000000',
            ]);

        $svg =
            $renderer->render(
                $barcodeData
            );

        if (trim($svg) === '') {
            throw InvalidTedDataException::because(
                'El renderer PDF417 produjo un SVG vacío.'
            );
        }

        return new DtePdf417RenderResult(
            svg: $svg,
            mimeType: 'image/svg+xml',
            columns: $barcodeData->columns,
            rows: $barcodeData->rows,
            securityLevel:
                $barcodeData->securityLevel,
            compactionCodeword:
                $compactionCodeword,
            moduleAspectRatio:
                self::MODULE_ASPECT_RATIO,
            payloadByteLength:
                strlen($tedLatin1),
            payloadSha256:
                hash(
                    'sha256',
                    $tedLatin1
                ),
        );
    }

    private function assertValidColumns(
        int $columns
    ): void {
        if (
            $columns < PDF417::MIN_COLUMNS
            || $columns > PDF417::MAX_COLUMNS
        ) {
            throw new \InvalidArgumentException(
                sprintf(
                    'La cantidad de columnas PDF417 debe estar entre %d y %d.',
                    PDF417::MIN_COLUMNS,
                    PDF417::MAX_COLUMNS
                )
            );
        }
    }
}