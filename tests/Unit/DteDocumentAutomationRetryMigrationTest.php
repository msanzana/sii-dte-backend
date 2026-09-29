<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Enums\DteStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class DteDocumentAutomationRetryMigrationTest
    extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
        |--------------------------------------------------------------------------
        | Tabla mínima anterior a la migración
        |--------------------------------------------------------------------------
        |
        | Este test NO ejecuta el historial completo de migraciones.
        |
        | Creamos únicamente la estructura mínima que necesita la migración
        | de document_retry.
        |
        | Es importante que estas tres columnas NO existan aquí:
        |
        | - automation_retry_action
        | - automation_retry_count
        | - automation_next_retry_at
        |
        */

        Schema::dropIfExists(
            'dte_documents'
        );

        Schema::create(
            'dte_documents',
            function (Blueprint $table): void {
                $table->id();

                $table->string(
                    'status',
                    40
                );
            }
        );
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists(
            'dte_documents'
        );

        parent::tearDown();
    }

    public function test_migracion_agrega_columnas_de_retry_de_automatizacion(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Ejecutar migration UP
        |--------------------------------------------------------------------------
        */

        $migration =
            $this->migration();

        $migration->up();

        /*
        |--------------------------------------------------------------------------
        | Columnas
        |--------------------------------------------------------------------------
        */

        $this->assertTrue(
            Schema::hasColumn(
                'dte_documents',
                'automation_retry_action'
            )
        );

        $this->assertTrue(
            Schema::hasColumn(
                'dte_documents',
                'automation_retry_count'
            )
        );

        $this->assertTrue(
            Schema::hasColumn(
                'dte_documents',
                'automation_next_retry_at'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Default del contador
        |--------------------------------------------------------------------------
        |
        | Un documento que todavía no ha entrado en retry debe comenzar
        | automáticamente con count = 0.
        |
        */

        $documentId =
            DB::table(
                'dte_documents'
            )
                ->insertGetId(
                    [
                        'status' =>
                            DteStatus::FOLIO_ASSIGNED->value,
                    ]
                );

        $retryCount =
            DB::table(
                'dte_documents'
            )
                ->where(
                    'id',
                    $documentId
                )
                ->value(
                    'automation_retry_count'
                );

        $this->assertSame(
            0,
            (int) $retryCount
        );

        /*
        |--------------------------------------------------------------------------
        | Índice
        |--------------------------------------------------------------------------
        |
        | PHPUnit usa SQLite :memory:, por lo que podemos inspeccionar
        | directamente los índices mediante PRAGMA.
        |
        */

        $indexes =
            DB::select(
                "PRAGMA index_list('dte_documents')"
            );

        $indexNames =
            array_map(
                static fn (object $index): string =>
                    (string) $index->name,
                $indexes
            );

        $this->assertContains(
            'dte_documents_automation_retry_idx',
            $indexNames
        );

        /*
        |--------------------------------------------------------------------------
        | Columnas exactas del índice
        |--------------------------------------------------------------------------
        */

        $indexColumns =
            DB::select(
                "PRAGMA index_info('dte_documents_automation_retry_idx')"
            );

        $columnNames =
            array_map(
                static fn (object $column): string =>
                    (string) $column->name,
                $indexColumns
            );

        $this->assertSame(
            [
                'status',
                'automation_next_retry_at',
            ],
            $columnNames
        );
    }

    public function test_down_elimina_columnas_e_indice_de_retry_de_automatizacion(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Preparar estado migrado
        |--------------------------------------------------------------------------
        */

        $migration =
            $this->migration();

        $migration->up();

        $this->assertTrue(
            Schema::hasColumn(
                'dte_documents',
                'automation_retry_action'
            )
        );

        $this->assertTrue(
            Schema::hasColumn(
                'dte_documents',
                'automation_retry_count'
            )
        );

        $this->assertTrue(
            Schema::hasColumn(
                'dte_documents',
                'automation_next_retry_at'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Ejecutar DOWN
        |--------------------------------------------------------------------------
        */

        $migration->down();

        /*
        |--------------------------------------------------------------------------
        | Las columnas deben desaparecer
        |--------------------------------------------------------------------------
        */

        $this->assertFalse(
            Schema::hasColumn(
                'dte_documents',
                'automation_retry_action'
            )
        );

        $this->assertFalse(
            Schema::hasColumn(
                'dte_documents',
                'automation_retry_count'
            )
        );

        $this->assertFalse(
            Schema::hasColumn(
                'dte_documents',
                'automation_next_retry_at'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | El índice también debe desaparecer
        |--------------------------------------------------------------------------
        */

        $indexes =
            DB::select(
                "PRAGMA index_list('dte_documents')"
            );

        $indexNames =
            array_map(
                static fn (object $index): string =>
                    (string) $index->name,
                $indexes
            );

        $this->assertNotContains(
            'dte_documents_automation_retry_idx',
            $indexNames
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Migration real
    |--------------------------------------------------------------------------
    |
    | Ejecutamos exclusivamente la migration que estamos probando.
    |
    | Esto evita pasar por migraciones históricas dependientes de MySQL,
    | por ejemplo aquellas que consultan information_schema.
    |
    */

    private function migration(): Migration
    {
        return require database_path(
            'migrations/2026_09_27_031336_add_automation_retry_columns_to_dte_documents_table.php'
        );
    }
}