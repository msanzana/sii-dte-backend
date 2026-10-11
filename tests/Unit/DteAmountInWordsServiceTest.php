<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\Services\DteAmountInWordsService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class DteAmountInWordsServiceTest extends TestCase
{
    private DteAmountInWordsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service =
            new DteAmountInWordsService();
    }

    #[DataProvider('amountProvider')]
    public function test_convierte_montos_a_palabras(
        int|float $amount,
        string $expected
    ): void {
        $this->assertSame(
            $expected,
            $this->service->toPesos(
                $amount
            )
        );
    }

    public static function amountProvider(): array
    {
        return [
            'cero' => [
                0,
                'CERO PESOS',
            ],

            'un peso' => [
                1,
                'UN PESO',
            ],

            'diez' => [
                10,
                'DIEZ PESOS',
            ],

            'veintiun' => [
                21,
                'VEINTIÚN PESOS',
            ],

            'veintidos' => [
                22,
                'VEINTIDÓS PESOS',
            ],

            'veintitres' => [
                23,
                'VEINTITRÉS PESOS',
            ],

            'veintiseis' => [
                26,
                'VEINTISÉIS PESOS',
            ],

            'treinta y un' => [
                31,
                'TREINTA Y UN PESOS',
            ],

            'cien' => [
                100,
                'CIEN PESOS',
            ],

            'ciento un' => [
                101,
                'CIENTO UN PESOS',
            ],

            'quinientos' => [
                500,
                'QUINIENTOS PESOS',
            ],

            'setecientos' => [
                700,
                'SETECIENTOS PESOS',
            ],

            'novecientos' => [
                900,
                'NOVECIENTOS PESOS',
            ],

            'mil' => [
                1000,
                'MIL PESOS',
            ],

            'mil un' => [
                1001,
                'MIL UN PESOS',
            ],

            'veintiun mil' => [
                21000,
                'VEINTIÚN MIL PESOS',
            ],

            'cien mil' => [
                100000,
                'CIEN MIL PESOS',
            ],

            'ciento un mil' => [
                101000,
                'CIENTO UN MIL PESOS',
            ],

            'dte real 51' => [
                47600,
                'CUARENTA Y SIETE MIL SEISCIENTOS PESOS',
            ],

            'un millon' => [
                1000000,
                'UN MILLÓN DE PESOS',
            ],

            'dos millones' => [
                2000000,
                'DOS MILLONES DE PESOS',
            ],

            'veintiun millones' => [
                21000000,
                'VEINTIÚN MILLONES DE PESOS',
            ],

            'millon con resto' => [
                1000001,
                'UN MILLÓN UN PESOS',
            ],

            'monto complejo' => [
                123456789,
                'CIENTO VEINTITRÉS MILLONES CUATROCIENTOS CINCUENTA Y SEIS MIL SETECIENTOS OCHENTA Y NUEVE PESOS',
            ],

            'float entero' => [
                47600.0,
                'CUARENTA Y SIETE MIL SEISCIENTOS PESOS',
            ],
        ];
    }

    public function test_rechaza_monto_negativo(): void
    {
        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->service->toPesos(
            -1
        );
    }

    public function test_rechaza_monto_con_decimales(): void
    {
        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->service->toPesos(
            47600.50
        );
    }
}