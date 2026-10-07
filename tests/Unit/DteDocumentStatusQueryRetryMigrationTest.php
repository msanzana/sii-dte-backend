<?php

namespace Tests\Unit;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

final class DteDocumentStatusQueryRetryMigrationTest
    extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_migracion_agrega_columnas_de_retry_de_consulta_documental(): void
    {
        $migration =
            $this->migration();

        $migration->up();

        $this->assertTrue(
            Schema::hasColumn(
                'dte_documents',
                'status_query_retry_count'
            )
        );

        $this->assertTrue(
            Schema::hasColumn(
                'dte_documents',
                'status_query_next_at'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Default del contador
        |--------------------------------------------------------------------------
        */

        $documentId =
            DB::table(
                'dte_documents'
            )
                ->insertGetId([
                    'status' => 'sent',
                ]);

        $retryCount =
            DB::table(
                'dte_documents'
            )
                ->where(
                    'id',
                    $documentId
                )
                ->value(
                    'status_query_retry_count'
                );

        $this->assertSame(
            0,
            (int) $retryCount
        );

        /*
        |--------------------------------------------------------------------------
        | Índice
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

        $this->assertContains(
            'dte_documents_status_query_retry_idx',
            $indexNames
        );

        $indexColumns =
            DB::select(
                "PRAGMA index_info('dte_documents_status_query_retry_idx')"
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
                'status_query_next_at',
            ],
            $columnNames
        );
    }

    public function test_down_elimina_columnas_e_indice_de_retry_de_consulta(): void
    {
        $migration =
            $this->migration();

        $migration->up();

        $this->assertTrue(
            Schema::hasColumn(
                'dte_documents',
                'status_query_retry_count'
            )
        );

        $this->assertTrue(
            Schema::hasColumn(
                'dte_documents',
                'status_query_next_at'
            )
        );

        $migration->down();

        $this->assertFalse(
            Schema::hasColumn(
                'dte_documents',
                'status_query_retry_count'
            )
        );

        $this->assertFalse(
            Schema::hasColumn(
                'dte_documents',
                'status_query_next_at'
            )
        );

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
            'dte_documents_status_query_retry_idx',
            $indexNames
        );
    }

    private function migration(): Migration
    {
        $files =
            glob(
                database_path(
                    'migrations/*_add_status_query_retry_columns_to_dte_documents_table.php'
                )
            );

        if (
            !is_array($files)
            || count($files) !== 1
        ) {
            throw new RuntimeException(
                'Debe existir exactamente una migration '
                . 'add_status_query_retry_columns_to_dte_documents_table.'
            );
        }

        $migration =
            require $files[0];

        if (!$migration instanceof Migration) {
            throw new RuntimeException(
                'La migration debe retornar una instancia de Migration.'
            );
        }

        return $migration;
    }
}