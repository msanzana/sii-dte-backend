<?php

namespace App\Modules\Dte\Application\Services;

final class DteAmountInWordsService
{
    public function toPesos(
        int|float $amount
    ): string {
        if (!is_finite((float) $amount)) {
            throw new \InvalidArgumentException(
                'El monto debe ser un número finito.'
            );
        }

        if ($amount < 0) {
            throw new \InvalidArgumentException(
                'El monto no puede ser negativo.'
            );
        }

        $rounded =
            round(
                (float) $amount
            );

        if (
            abs(
                (float) $amount
                - $rounded
            ) > 0.000001
        ) {
            throw new \InvalidArgumentException(
                'El monto en pesos debe ser entero.'
            );
        }

        if ($rounded > PHP_INT_MAX) {
            throw new \InvalidArgumentException(
                'El monto excede la capacidad soportada.'
            );
        }

        $value =
            (int) $rounded;

        if ($value === 0) {
            return 'CERO PESOS';
        }

        $words =
            $this->convertInteger(
                $value,
                true
            );

        if ($value === 1) {
            return $words . ' PESO';
        }

        /*
         * En español corresponde "DE PESOS" cuando
         * el numeral termina exactamente en un múltiplo
         * de un millón.
         *
         * Ejemplos:
         *   1.000.000 -> UN MILLÓN DE PESOS
         *   2.000.000 -> DOS MILLONES DE PESOS
         *
         * Pero:
         *   1.000.001 -> UN MILLÓN UN PESOS
         */
        if (
            $value >= 1_000_000
            && $value % 1_000_000 === 0
        ) {
            return $words . ' DE PESOS';
        }

        return $words . ' PESOS';
    }

    private function convertInteger(
        int $number,
        bool $apocopate
    ): string {
        if ($number < 1_000_000) {
            return $this->convertBelowMillion(
                $number,
                $apocopate
            );
        }

        if ($number < 1_000_000_000_000) {
            $millions =
                intdiv(
                    $number,
                    1_000_000
                );

            $remainder =
                $number
                % 1_000_000;

            if ($millions === 1) {
                $result =
                    'UN MILLÓN';
            } else {
                $result =
                    $this->convertBelowMillion(
                        $millions,
                        true
                    )
                    . ' MILLONES';
            }

            if ($remainder > 0) {
                $result .=
                    ' '
                    . $this->convertBelowMillion(
                        $remainder,
                        $apocopate
                    );
            }

            return $result;
        }

        /*
         * Escala larga española:
         * 1 billón = 1.000.000.000.000
         */
        $billions =
            intdiv(
                $number,
                1_000_000_000_000
            );

        $remainder =
            $number
            % 1_000_000_000_000;

        if ($billions === 1) {
            $result =
                'UN BILLÓN';
        } else {
            $result =
                $this->convertInteger(
                    $billions,
                    true
                )
                . ' BILLONES';
        }

        if ($remainder > 0) {
            $result .=
                ' '
                . $this->convertInteger(
                    $remainder,
                    $apocopate
                );
        }

        return $result;
    }

    private function convertBelowMillion(
        int $number,
        bool $apocopate
    ): string {
        if ($number < 1000) {
            return $this->convertHundreds(
                $number,
                $apocopate
            );
        }

        $thousands =
            intdiv(
                $number,
                1000
            );

        $remainder =
            $number
            % 1000;

        if ($thousands === 1) {
            $result =
                'MIL';
        } else {
            $result =
                $this->convertHundreds(
                    $thousands,
                    true
                )
                . ' MIL';
        }

        if ($remainder > 0) {
            $result .=
                ' '
                . $this->convertHundreds(
                    $remainder,
                    $apocopate
                );
        }

        return $result;
    }

    private function convertHundreds(
        int $number,
        bool $apocopate
    ): string {
        if ($number < 100) {
            return $this->convertBelowHundred(
                $number,
                $apocopate
            );
        }

        if ($number === 100) {
            return 'CIEN';
        }

        $hundreds =
            intdiv(
                $number,
                100
            );

        $remainder =
            $number
            % 100;

        $prefix =
            match ($hundreds) {
                1 =>
                    'CIENTO',

                2 =>
                    'DOSCIENTOS',

                3 =>
                    'TRESCIENTOS',

                4 =>
                    'CUATROCIENTOS',

                5 =>
                    'QUINIENTOS',

                6 =>
                    'SEISCIENTOS',

                7 =>
                    'SETECIENTOS',

                8 =>
                    'OCHOCIENTOS',

                9 =>
                    'NOVECIENTOS',

                default =>
                    throw new \LogicException(
                        'Centena fuera de rango.'
                    ),
            };

        if ($remainder === 0) {
            return $prefix;
        }

        return
            $prefix
            . ' '
            . $this->convertBelowHundred(
                $remainder,
                $apocopate
            );
    }

    private function convertBelowHundred(
        int $number,
        bool $apocopate
    ): string {
        if (
            $number < 0
            || $number >= 100
        ) {
            throw new \LogicException(
                'El número debe estar entre 0 y 99.'
            );
        }

        if ($number <= 29) {
            return $this->convertSpecialNumber(
                $number,
                $apocopate
            );
        }

        $tens =
            intdiv(
                $number,
                10
            );

        $unit =
            $number
            % 10;

        $tensText =
            match ($tens) {
                3 =>
                    'TREINTA',

                4 =>
                    'CUARENTA',

                5 =>
                    'CINCUENTA',

                6 =>
                    'SESENTA',

                7 =>
                    'SETENTA',

                8 =>
                    'OCHENTA',

                9 =>
                    'NOVENTA',

                default =>
                    throw new \LogicException(
                        'Decena fuera de rango.'
                    ),
            };

        if ($unit === 0) {
            return $tensText;
        }

        return
            $tensText
            . ' Y '
            . $this->convertUnit(
                $unit,
                $apocopate
            );
    }

    private function convertSpecialNumber(
        int $number,
        bool $apocopate
    ): string {
        return match ($number) {
            0 =>
                'CERO',

            1 =>
                $apocopate
                    ? 'UN'
                    : 'UNO',

            2 =>
                'DOS',

            3 =>
                'TRES',

            4 =>
                'CUATRO',

            5 =>
                'CINCO',

            6 =>
                'SEIS',

            7 =>
                'SIETE',

            8 =>
                'OCHO',

            9 =>
                'NUEVE',

            10 =>
                'DIEZ',

            11 =>
                'ONCE',

            12 =>
                'DOCE',

            13 =>
                'TRECE',

            14 =>
                'CATORCE',

            15 =>
                'QUINCE',

            16 =>
                'DIECISÉIS',

            17 =>
                'DIECISIETE',

            18 =>
                'DIECIOCHO',

            19 =>
                'DIECINUEVE',

            20 =>
                'VEINTE',

            21 =>
                $apocopate
                    ? 'VEINTIÚN'
                    : 'VEINTIUNO',

            22 =>
                'VEINTIDÓS',

            23 =>
                'VEINTITRÉS',

            24 =>
                'VEINTICUATRO',

            25 =>
                'VEINTICINCO',

            26 =>
                'VEINTISÉIS',

            27 =>
                'VEINTISIETE',

            28 =>
                'VEINTIOCHO',

            29 =>
                'VEINTINUEVE',

            default =>
                throw new \LogicException(
                    'Número especial fuera de rango.'
                ),
        };
    }

    private function convertUnit(
        int $unit,
        bool $apocopate
    ): string {
        return match ($unit) {
            1 =>
                $apocopate
                    ? 'UN'
                    : 'UNO',

            2 =>
                'DOS',

            3 =>
                'TRES',

            4 =>
                'CUATRO',

            5 =>
                'CINCO',

            6 =>
                'SEIS',

            7 =>
                'SIETE',

            8 =>
                'OCHO',

            9 =>
                'NUEVE',

            default =>
                throw new \LogicException(
                    'Unidad fuera de rango.'
                ),
        };
    }
}