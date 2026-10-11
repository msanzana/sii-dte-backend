<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class DteCediblePrintRequirementMigrationTest extends TestCase
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

                $table->boolean('is_required')
                    ->default(false);

                $table->json('parameters')
                    ->nullable();

                $table->timestamps();
            }
        );

        foreach ([33, 34, 46, 52] as $code) {
            $id = DB::table(
                'dte_document_types'
            )->insertGetId([
                'code' => $code,
            ]);

            DB::table(
                'dte_document_type_print_copies'
            )->insert([
                'dte_document_type_id' => $id,
                'code' => 'cedible',
                'is_required' => false,
                'parameters' => json_encode(
                    [
                        'conditional' => true,
                    ],
                    JSON_THROW_ON_ERROR
                ),
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

    public function test_cedibles_quedan_como_obligatorios(): void
    {
        $migration = $this->loadMigration();

        $migration->up();

        $rows = DB::table(
            'dte_document_type_print_copies'
        )
            ->where('code', 'cedible')
            ->get();

        $this->assertCount(4, $rows);

        foreach ($rows as $row) {
            $this->assertSame(
                1,
                (int) $row->is_required
            );

            $this->assertNull(
                $row->parameters
            );
        }
    }

    public function test_down_restaura_configuracion_anterior(): void
    {
        $migration = $this->loadMigration();

        $migration->up();
        $migration->down();

        $rows = DB::table(
            'dte_document_type_print_copies as c'
        )
            ->join(
                'dte_document_types as t',
                't.id',
                '=',
                'c.dte_document_type_id'
            )
            ->orderBy('t.code')
            ->get([
                't.code',
                'c.is_required',
                'c.parameters',
            ]);

        $this->assertCount(4, $rows);

        foreach ($rows as $row) {
            $this->assertSame(
                0,
                (int) $row->is_required
            );

            $this->assertNotNull(
                $row->parameters
            );
        }
    }

    private function loadMigration(): object
    {
        $matches = glob(
            database_path(
                'migrations/*_make_cedible_print_copies_required.php'
            )
        );

        if (
            $matches === false
            || count($matches) !== 1
        ) {
            throw new \RuntimeException(
                'No se encontró exactamente una migración correctiva de copias cedibles.'
            );
        }

        return require $matches[0];
    }
}