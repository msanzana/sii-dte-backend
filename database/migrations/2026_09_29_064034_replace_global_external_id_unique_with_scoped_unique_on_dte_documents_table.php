<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const GLOBAL_UNIQUE =
        'uq_dte_documents_external_id';

    private const SCOPED_UNIQUE =
        'uq_dte_documents_company_system_external_id';

    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Eliminar UNIQUE global de external_id
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'dte_documents',
            function (Blueprint $table): void {
                $table->dropUnique(
                    self::GLOBAL_UNIQUE
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Normalizar external_system_id
        |--------------------------------------------------------------------------
        |
        | MySQL/MariaDB permiten múltiples NULL dentro de un índice UNIQUE.
        |
        | Por eso external_system_id = NULL se representa internamente como
        | scope 0 para efectos exclusivos de la restricción de unicidad.
        |
        | Es una columna generada: la aplicación nunca debe escribirla.
        |
        */

        Schema::table(
            'dte_documents',
            function (Blueprint $table): void {
                $table
                    ->unsignedBigInteger(
                        'external_system_scope_id'
                    )
                    ->virtualAs(
                        'COALESCE(external_system_id, 0)'
                    );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | UNIQUE por empresa + ámbito + external_id
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'dte_documents',
            function (Blueprint $table): void {
                $table->unique(
                    [
                        'company_id',
                        'external_system_scope_id',
                        'external_id',
                    ],
                    self::SCOPED_UNIQUE
                );
            }
        );
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Quitar UNIQUE scoped
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'dte_documents',
            function (Blueprint $table): void {
                $table->dropUnique(
                    self::SCOPED_UNIQUE
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Quitar columna generada
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'dte_documents',
            function (Blueprint $table): void {
                $table->dropColumn(
                    'external_system_scope_id'
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Restaurar comportamiento histórico
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'dte_documents',
            function (Blueprint $table): void {
                $table->unique(
                    'external_id',
                    self::GLOBAL_UNIQUE
                );
            }
        );
    }
};