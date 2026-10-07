<?php

namespace Tests\Unit;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

final class DteDocumentExternalIdScopedUniqueMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create(
            'dte_documents',
            function (Blueprint $table): void {
                $table->id();

                $table->uuid(
                    'external_id'
                );

                $table->unsignedBigInteger(
                    'company_id'
                );

                $table->unsignedBigInteger(
                    'external_system_id'
                )->nullable();

                $table->unique(
                    'external_id',
                    'uq_dte_documents_external_id'
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

    public function test_up_reemplaza_unique_global_por_unique_por_empresa_y_ambito(): void
    {
        $migration =
            $this->migration();

        $migration->up();

        /*
        |--------------------------------------------------------------------------
        | Columna normalizada del ámbito
        |--------------------------------------------------------------------------
        */

        $this->assertTrue(
            Schema::hasColumn(
                'dte_documents',
                'external_system_scope_id'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Índice UNIQUE compuesto
        |--------------------------------------------------------------------------
        */

        $index =
            $this->sqliteIndex(
                'uq_dte_documents_company_system_external_id'
            );

        $this->assertNotNull(
            $index
        );

        $this->assertSame(
            1,
            (int) $index->unique
        );

        $this->assertSame(
            [
                'company_id',
                'external_system_scope_id',
                'external_id',
            ],
            $this->sqliteIndexColumns(
                'uq_dte_documents_company_system_external_id'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | El UNIQUE global anterior desapareció
        |--------------------------------------------------------------------------
        */

        $this->assertNull(
            $this->sqliteIndex(
                'uq_dte_documents_external_id'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Mismo external_id en distintos ámbitos: permitido
        |--------------------------------------------------------------------------
        */

        $externalId =
            'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

        DB::table(
            'dte_documents'
        )->insert([
            'external_id' =>
                $externalId,

            'company_id' =>
                1,

            'external_system_id' =>
                10,
        ]);

        DB::table(
            'dte_documents'
        )->insert([
            'external_id' =>
                $externalId,

            'company_id' =>
                1,

            'external_system_id' =>
                20,
        ]);

        DB::table(
            'dte_documents'
        )->insert([
            'external_id' =>
                $externalId,

            'company_id' =>
                2,

            'external_system_id' =>
                10,
        ]);

        $this->assertSame(
            3,
            DB::table(
                'dte_documents'
            )
                ->where(
                    'external_id',
                    $externalId
                )
                ->count()
        );

        /*
        |--------------------------------------------------------------------------
        | Mismo ámbito: bloqueado por BD
        |--------------------------------------------------------------------------
        */

        try {
            DB::table(
                'dte_documents'
            )->insert([
                'external_id' =>
                    $externalId,

                'company_id' =>
                    1,

                'external_system_id' =>
                    10,
            ]);

            $this->fail(
                'La BD debía rechazar un external_id duplicado en el mismo ámbito.'
            );

        } catch (
            QueryException
        ) {
            $this->assertTrue(
                true
            );
        }
    }

    public function test_external_system_null_tambien_queda_protegido_por_unique(): void
    {
        $migration =
            $this->migration();

        $migration->up();

        $externalId =
            'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

        DB::table(
            'dte_documents'
        )->insert([
            'external_id' =>
                $externalId,

            'company_id' =>
                1,

            'external_system_id' =>
                null,
        ]);

        $row =
            DB::table(
                'dte_documents'
            )
                ->where(
                    'external_id',
                    $externalId
                )
                ->first();

        /*
        | NULL se normaliza al scope reservado 0.
        */

        $this->assertSame(
            0,
            (int) $row->external_system_scope_id
        );

        try {
            DB::table(
                'dte_documents'
            )->insert([
                'external_id' =>
                    $externalId,

                'company_id' =>
                    1,

                'external_system_id' =>
                    null,
            ]);

            $this->fail(
                'La BD debía rechazar un external_id interno duplicado.'
            );

        } catch (
            QueryException
        ) {
            $this->assertTrue(
                true
            );
        }

        /*
        | Otra empresa con scope interno sigue siendo válida.
        */

        DB::table(
            'dte_documents'
        )->insert([
            'external_id' =>
                $externalId,

            'company_id' =>
                2,

            'external_system_id' =>
                null,
        ]);

        $this->assertSame(
            2,
            DB::table(
                'dte_documents'
            )
                ->where(
                    'external_id',
                    $externalId
                )
                ->count()
        );
    }

    public function test_down_restaura_unique_global_original(): void
    {
        $migration =
            $this->migration();

        $migration->up();
        $migration->down();

        $this->assertFalse(
            Schema::hasColumn(
                'dte_documents',
                'external_system_scope_id'
            )
        );

        $globalIndex =
            $this->sqliteIndex(
                'uq_dte_documents_external_id'
            );

        $this->assertNotNull(
            $globalIndex
        );

        $this->assertSame(
            1,
            (int) $globalIndex->unique
        );

        $this->assertSame(
            [
                'external_id',
            ],
            $this->sqliteIndexColumns(
                'uq_dte_documents_external_id'
            )
        );
    }

    private function migration(): object
    {
        $files =
            glob(
                database_path(
                    'migrations/*_replace_global_external_id_unique_with_scoped_unique_on_dte_documents_table.php'
                )
            );

        if (
            $files === false
            || count($files) !== 1
        ) {
            throw new RuntimeException(
                'No se encontró exactamente una migración de external_id scoped.'
            );
        }

        return require $files[0];
    }

    private function sqliteIndex(
        string $name
    ): ?object {
        $indexes =
            DB::select(
                "PRAGMA index_list('dte_documents')"
            );

        foreach (
            $indexes as $index
        ) {
            if (
                $index->name === $name
            ) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function sqliteIndexColumns(
        string $name
    ): array {
        return array_map(
            static fn (
                object $column
            ): string =>
                $column->name,

            DB::select(
                "PRAGMA index_info('{$name}')"
            )
        );
    }
}
