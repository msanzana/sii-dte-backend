<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

final class DtePrintCopyCatalogMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists(
            'dte_document_type_print_copies'
        );

        Schema::dropIfExists(
            'dte_document_types'
        );

        Schema::create(
            'dte_document_types',
            function (Blueprint $table): void {
                $table->id();
                $table->unsignedSmallInteger('code')
                    ->unique();

                $table->string('name', 150)
                    ->nullable();
            }
        );

        Schema::create(
            'dte_document_type_print_copies',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'dte_document_type_id'
                );

                $table->string('code', 40);
                $table->string('name', 120);

                $table->boolean('is_required')
                    ->default(true);

                $table->unsignedSmallInteger(
                    'default_quantity'
                )->default(1);

                $table->boolean('is_active')
                    ->default(true);

                $table->unsignedSmallInteger(
                    'sort_order'
                )->default(0);

                $table->string('legend', 180)
                    ->nullable();

                $table->json('parameters')
                    ->nullable();

                $table->timestamps();

                $table->unique([
                    'dte_document_type_id',
                    'code',
                ]);
            }
        );

        foreach ([
            33,
            34,
            39,
            41,
            46,
            52,
            56,
            61,
        ] as $code) {
            DB::table('dte_document_types')
                ->insert([
                    'code' => $code,
                    'name' => 'DTE ' . $code,
                ]);
        }
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists(
            'dte_document_type_print_copies'
        );

        Schema::dropIfExists(
            'dte_document_types'
        );

        parent::tearDown();
    }

    public function test_migracion_crea_matriz_inicial_de_impresion(): void
    {
        $migration = $this->loadMigration();

        $migration->up();

        $this->assertSame(
            18,
            DB::table(
                'dte_document_type_print_copies'
            )->count()
        );

        $this->assertSame(
            8,
            $this->countCopyType('original')
        );

        $this->assertSame(
            6,
            $this->countCopyType('client')
        );

        $this->assertSame(
            4,
            $this->countCopyType('cedible')
        );
    }

    public function test_solo_tipos_legales_configurados_tienen_cedible(): void
    {
        $migration = $this->loadMigration();

        $migration->up();

        $codes = DB::table(
            'dte_document_type_print_copies as c'
        )
            ->join(
                'dte_document_types as t',
                't.id',
                '=',
                'c.dte_document_type_id'
            )
            ->where('c.code', 'cedible')
            ->orderBy('t.code')
            ->pluck('t.code')
            ->map(
                static fn ($code): int => (int) $code
            )
            ->all();

        $this->assertSame(
            [33, 34, 46, 52],
            $codes
        );
    }

    public function test_guia_usa_leyenda_cedible_con_su_factura(): void
    {
        $migration = $this->loadMigration();

        $migration->up();

        $legend = DB::table(
            'dte_document_type_print_copies as c'
        )
            ->join(
                'dte_document_types as t',
                't.id',
                '=',
                'c.dte_document_type_id'
            )
            ->where('t.code', 52)
            ->where('c.code', 'cedible')
            ->value('c.legend');

        $this->assertSame(
            'CEDIBLE CON SU FACTURA',
            $legend
        );
    }

    public function test_boletas_no_tienen_copia_cedible(): void
    {
        $migration = $this->loadMigration();

        $migration->up();

        foreach ([39, 41] as $dteType) {
            $exists = DB::table(
                'dte_document_type_print_copies as c'
            )
                ->join(
                    'dte_document_types as t',
                    't.id',
                    '=',
                    'c.dte_document_type_id'
                )
                ->where('t.code', $dteType)
                ->where('c.code', 'cedible')
                ->exists();

            $this->assertFalse($exists);
        }
    }

    public function test_down_elimina_solo_la_matriz_inicial(): void
    {
        $migration = $this->loadMigration();

        $migration->up();

        $migration->down();

        $this->assertSame(
            0,
            DB::table(
                'dte_document_type_print_copies'
            )->count()
        );
    }

    private function countCopyType(
        string $code
    ): int {
        return DB::table(
            'dte_document_type_print_copies'
        )
            ->where('code', $code)
            ->count();
    }

    private function loadMigration(): object
    {
        $matches = glob(
            database_path(
                'migrations/*_seed_initial_dte_print_copy_matrix.php'
            )
        );

        if (
            $matches === false
            || count($matches) !== 1
        ) {
            throw new RuntimeException(
                'No se encontró exactamente una migración de matriz inicial de impresión.'
            );
        }

        return require $matches[0];
    }
}