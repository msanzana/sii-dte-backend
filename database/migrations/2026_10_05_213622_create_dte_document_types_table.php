<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dte_document_types', function (Blueprint $table) {
            $table->id();

            $table->unsignedSmallInteger('code');

            $table->string('name', 150);

            /*
             * Clasificación funcional.
             *
             * factura
             * boleta
             * compra
             * guia
             * nota
             */
            $table->string('family', 30);

            /*
             * Familia técnica utilizada para comunicación con SII.
             *
             * factura -> SOAP
             * boleta  -> REST
             */
            $table->string('transport_family', 20);

            $table->boolean('is_active')
                ->default(true);

            $table->unsignedSmallInteger('sort_order')
                ->default(0);

            /*
             * Permite agregar parámetros futuros sin modificar
             * inmediatamente el esquema.
             */
            $table->json('parameters')->nullable();

            $table->timestamps();

            $table->unique(
                'code',
                'uq_dte_document_types_code'
            );

            $table->index(
                ['family', 'is_active'],
                'idx_dte_document_types_family_active'
            );

            $table->index(
                ['transport_family', 'is_active'],
                'idx_dte_document_types_transport_active'
            );
        });

        $now = now();

        DB::table('dte_document_types')->insert([
            [
                'code' => 33,
                'name' => 'Factura Electrónica',
                'family' => 'factura',
                'transport_family' => 'factura',
                'is_active' => true,
                'sort_order' => 10,
                'parameters' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 34,
                'name' => 'Factura No Afecta o Exenta Electrónica',
                'family' => 'factura',
                'transport_family' => 'factura',
                'is_active' => true,
                'sort_order' => 20,
                'parameters' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 39,
                'name' => 'Boleta Electrónica',
                'family' => 'boleta',
                'transport_family' => 'boleta',
                'is_active' => true,
                'sort_order' => 30,
                'parameters' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 41,
                'name' => 'Boleta No Afecta o Exenta Electrónica',
                'family' => 'boleta',
                'transport_family' => 'boleta',
                'is_active' => true,
                'sort_order' => 40,
                'parameters' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 46,
                'name' => 'Factura de Compra Electrónica',
                'family' => 'compra',
                'transport_family' => 'factura',
                'is_active' => true,
                'sort_order' => 50,
                'parameters' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 52,
                'name' => 'Guía de Despacho Electrónica',
                'family' => 'guia',
                'transport_family' => 'factura',
                'is_active' => true,
                'sort_order' => 60,
                'parameters' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 56,
                'name' => 'Nota de Débito Electrónica',
                'family' => 'nota',
                'transport_family' => 'factura',
                'is_active' => true,
                'sort_order' => 70,
                'parameters' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 61,
                'name' => 'Nota de Crédito Electrónica',
                'family' => 'nota',
                'transport_family' => 'factura',
                'is_active' => true,
                'sort_order' => 80,
                'parameters' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('dte_document_types');
    }
};