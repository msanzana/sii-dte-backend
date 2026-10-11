<?php

namespace App\Modules\Dte\Domain\Services;

use App\Modules\Dte\Domain\Exceptions\InvalidTedDataException;

final class TedEncodingService
{
    public static function normalizeUtf8Text(
        string $value
    ): string {
        $collapsed = preg_replace(
            '/\s+/u',
            ' ',
            trim($value)
        );

        if (
            $collapsed === null
            || $collapsed === ''
        ) {
            throw InvalidTedDataException::because(
                'Uno de los textos del TED quedó vacío después de normalizar espacios.'
            );
        }

        $latin1 = self::utf8ToLatin1Strict(
            $collapsed,
            'texto del TED'
        );

        return self::latin1ToUtf8Strict(
            $latin1,
            'texto del TED'
        );
    }

    public static function utf8ToLatin1Strict(
        string $value,
        string $context = 'contenido del TED'
    ): string {
        if ($value === '') {
            throw InvalidTedDataException::because(
                sprintf(
                    'El %s está vacío.',
                    $context
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Conversión estricta
        |--------------------------------------------------------------------------
        |
        | No utilizar TRANSLIT ni IGNORE.
        |
        | Si un carácter UTF-8 no puede representarse exactamente en
        | ISO-8859-1, la operación debe fallar en vez de modificar o eliminar
        | silenciosamente ese carácter.
        |
        */

        $latin1 = @iconv(
            'UTF-8',
            'ISO-8859-1',
            $value
        );

        if (
            $latin1 === false
            || $latin1 === ''
        ) {
            throw InvalidTedDataException::because(
                sprintf(
                    'El %s contiene caracteres que no pueden representarse exactamente en ISO-8859-1.',
                    $context
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Verificación round-trip
        |--------------------------------------------------------------------------
        |
        | Convertimos nuevamente a UTF-8 y exigimos igualdad byte a byte con
        | la cadena original. Así evitamos cualquier transformación silenciosa.
        |
        */

        $roundTrip = @iconv(
            'ISO-8859-1',
            'UTF-8',
            $latin1
        );

        if (
            $roundTrip === false
            || $roundTrip !== $value
        ) {
            throw InvalidTedDataException::because(
                sprintf(
                    'El %s no conserva exactamente su contenido al convertirlo a ISO-8859-1.',
                    $context
                )
            );
        }

        return $latin1;
    }

    public static function latin1ToUtf8Strict(
        string $value,
        string $context = 'contenido del TED'
    ): string {
        if ($value === '') {
            throw InvalidTedDataException::because(
                sprintf(
                    'El %s está vacío.',
                    $context
                )
            );
        }

        $utf8 = @iconv(
            'ISO-8859-1',
            'UTF-8',
            $value
        );

        if (
            $utf8 === false
            || $utf8 === ''
        ) {
            throw InvalidTedDataException::because(
                sprintf(
                    'No fue posible convertir el %s desde ISO-8859-1 a UTF-8.',
                    $context
                )
            );
        }

        return $utf8;
    }
}