<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $typeIds = DB::table('dte_document_types')
                ->whereIn('code', [
                    33,
                    34,
                    39,
                    41,
                    46,
                    52,
                    56,
                    61,
                ])
                ->pluck('id', 'code');

            if ($typeIds->count() !== 8) {
                throw new \RuntimeException(
                    'No se encontraron los 8 tipos DTE requeridos para inicializar la configuración de impresión.'
                );
            }

            $rows = [
                // Factura Electrónica - 33
                [
                    'dte_type' => 33,
                    'code' => 'original',
                    'name' => 'Original',
                    'is_required' => true,
                    'default_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => 10,
                    'legend' => null,
                    'parameters' => null,
                ],
                [
                    'dte_type' => 33,
                    'code' => 'client',
                    'name' => 'Copia Cliente',
                    'is_required' => false,
                    'default_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => 20,
                    'legend' => 'COPIA CLIENTE',
                    'parameters' => null,
                ],
                [
                    'dte_type' => 33,
                    'code' => 'cedible',
                    'name' => 'Cedible',
                    'is_required' => false,
                    'default_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => 30,
                    'legend' => 'CEDIBLE',
                    'parameters' => json_encode([
                        'conditional' => true,
                        'rule' => 'cedible_when_legally_applicable',
                    ], JSON_THROW_ON_ERROR),
                ],

                // Factura No Afecta o Exenta Electrónica - 34
                [
                    'dte_type' => 34,
                    'code' => 'original',
                    'name' => 'Original',
                    'is_required' => true,
                    'default_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => 10,
                    'legend' => null,
                    'parameters' => null,
                ],
                [
                    'dte_type' => 34,
                    'code' => 'client',
                    'name' => 'Copia Cliente',
                    'is_required' => false,
                    'default_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => 20,
                    'legend' => 'COPIA CLIENTE',
                    'parameters' => null,
                ],
                [
                    'dte_type' => 34,
                    'code' => 'cedible',
                    'name' => 'Cedible',
                    'is_required' => false,
                    'default_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => 30,
                    'legend' => 'CEDIBLE',
                    'parameters' => json_encode([
                        'conditional' => true,
                        'rule' => 'cedible_when_legally_applicable',
                    ], JSON_THROW_ON_ERROR),
                ],

                // Boleta Electrónica - 39
                [
                    'dte_type' => 39,
                    'code' => 'original',
                    'name' => 'Original',
                    'is_required' => true,
                    'default_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => 10,
                    'legend' => null,
                    'parameters' => null,
                ],

                // Boleta No Afecta o Exenta Electrónica - 41
                [
                    'dte_type' => 41,
                    'code' => 'original',
                    'name' => 'Original',
                    'is_required' => true,
                    'default_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => 10,
                    'legend' => null,
                    'parameters' => null,
                ],

                // Factura de Compra Electrónica - 46
                [
                    'dte_type' => 46,
                    'code' => 'original',
                    'name' => 'Original',
                    'is_required' => true,
                    'default_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => 10,
                    'legend' => null,
                    'parameters' => null,
                ],
                [
                    'dte_type' => 46,
                    'code' => 'client',
                    'name' => 'Copia Cliente',
                    'is_required' => false,
                    'default_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => 20,
                    'legend' => 'COPIA CLIENTE',
                    'parameters' => null,
                ],
                [
                    'dte_type' => 46,
                    'code' => 'cedible',
                    'name' => 'Cedible',
                    'is_required' => false,
                    'default_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => 30,
                    'legend' => 'CEDIBLE',
                    'parameters' => json_encode([
                        'conditional' => true,
                        'rule' => 'cedible_when_legally_applicable',
                    ], JSON_THROW_ON_ERROR),
                ],

                // Guía de Despacho Electrónica - 52
                [
                    'dte_type' => 52,
                    'code' => 'original',
                    'name' => 'Original',
                    'is_required' => true,
                    'default_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => 10,
                    'legend' => null,
                    'parameters' => null,
                ],
                [
                    'dte_type' => 52,
                    'code' => 'client',
                    'name' => 'Copia Cliente',
                    'is_required' => false,
                    'default_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => 20,
                    'legend' => 'COPIA CLIENTE',
                    'parameters' => null,
                ],
                [
                    'dte_type' => 52,
                    'code' => 'cedible',
                    'name' => 'Cedible con su Factura',
                    'is_required' => false,
                    'default_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => 30,
                    'legend' => 'CEDIBLE CON SU FACTURA',
                    'parameters' => json_encode([
                        'conditional' => true,
                        'rule' => 'cedible_with_invoice_when_applicable',
                    ], JSON_THROW_ON_ERROR),
                ],

                // Nota de Débito Electrónica - 56
                [
                    'dte_type' => 56,
                    'code' => 'original',
                    'name' => 'Original',
                    'is_required' => true,
                    'default_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => 10,
                    'legend' => null,
                    'parameters' => null,
                ],
                [
                    'dte_type' => 56,
                    'code' => 'client',
                    'name' => 'Copia Cliente',
                    'is_required' => false,
                    'default_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => 20,
                    'legend' => 'COPIA CLIENTE',
                    'parameters' => null,
                ],

                // Nota de Crédito Electrónica - 61
                [
                    'dte_type' => 61,
                    'code' => 'original',
                    'name' => 'Original',
                    'is_required' => true,
                    'default_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => 10,
                    'legend' => null,
                    'parameters' => null,
                ],
                [
                    'dte_type' => 61,
                    'code' => 'client',
                    'name' => 'Copia Cliente',
                    'is_required' => false,
                    'default_quantity' => 1,
                    'is_active' => true,
                    'sort_order' => 20,
                    'legend' => 'COPIA CLIENTE',
                    'parameters' => null,
                ],
            ];

            $now = now();

            foreach ($rows as $row) {
                $dteType = $row['dte_type'];

                unset($row['dte_type']);

                DB::table(
                    'dte_document_type_print_copies'
                )->updateOrInsert(
                    [
                        'dte_document_type_id' =>
                            (int) $typeIds[$dteType],
                        'code' => $row['code'],
                    ],
                    [
                        ...$row,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        });
    }

    public function down(): void
    {
        $typeIds = DB::table('dte_document_types')
            ->whereIn('code', [
                33,
                34,
                39,
                41,
                46,
                52,
                56,
                61,
            ])
            ->pluck('id');

        DB::table('dte_document_type_print_copies')
            ->whereIn(
                'dte_document_type_id',
                $typeIds
            )
            ->whereIn(
                'code',
                [
                    'original',
                    'client',
                    'cedible',
                ]
            )
            ->delete();
    }
};