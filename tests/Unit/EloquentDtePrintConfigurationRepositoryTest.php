<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Enums\PrintCopyType;
use App\Modules\Dte\Domain\Enums\PrintFormat;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentDtePrintConfigurationRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class EloquentDtePrintConfigurationRepositoryTest extends TestCase
{
    private EloquentDtePrintConfigurationRepository $repository;

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
                $table->unsignedSmallInteger('code')->unique();
                $table->string('name', 150);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_printable')->default(true);

                $table->string(
                    'default_print_format',
                    30
                )->default('letter');

                $table->json('print_parameters')->nullable();
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
            }
        );

        $this->repository =
            new EloquentDtePrintConfigurationRepository();
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

    public function test_carga_configuracion_y_copias_de_un_tipo_dte(): void
    {
        $typeId = $this->insertType(
            code: 33,
            name: 'Factura Electrónica',
            format: PrintFormat::LETTER,
            printParameters: [
                'allow_ticket' => false,
            ],
        );

        $this->insertCopy(
            typeId: $typeId,
            type: PrintCopyType::ORIGINAL,
            isActive: true,
            sortOrder: 10,
            defaultQuantity: 2,
        );

        $this->insertCopy(
            typeId: $typeId,
            type: PrintCopyType::CLIENT,
            isActive: false,
            sortOrder: 20,
        );

        $this->insertCopy(
            typeId: $typeId,
            type: PrintCopyType::CEDIBLE,
            isActive: true,
            sortOrder: 30,
            legend: 'CEDIBLE',
        );

        $configuration =
            $this->repository->findByDteTypeCode(33);

        $this->assertNotNull($configuration);

        $this->assertSame(
            $typeId,
            $configuration->dteDocumentTypeId()
        );

        $this->assertSame(
            33,
            $configuration->dteType()
        );

        $this->assertSame(
            'Factura Electrónica',
            $configuration->name()
        );

        $this->assertTrue(
            $configuration->isActive()
        );

        $this->assertTrue(
            $configuration->isPrintable()
        );

        $this->assertTrue(
            $configuration->isRenderable()
        );

        $this->assertSame(
            PrintFormat::LETTER,
            $configuration->defaultFormat()
        );

        $this->assertSame(
            ['allow_ticket' => false],
            $configuration->printParameters()
        );

        $this->assertCount(
            3,
            $configuration->copies()
        );
        $this->assertSame(
            2,
            $configuration->copies()[0]->defaultQuantity()
        );

        $this->assertCount(
            2,
            $configuration->activeCopies()
        );

        $this->assertTrue(
            $configuration->hasActiveCopy(
                PrintCopyType::ORIGINAL
            )
        );

        $this->assertFalse(
            $configuration->hasActiveCopy(
                PrintCopyType::CLIENT
            )
        );

        $this->assertTrue(
            $configuration->hasActiveCopy(
                PrintCopyType::CEDIBLE
            )
        );
    }

    public function test_tipo_dte_inexistente_retorna_null(): void
    {
        $this->assertNull(
            $this->repository->findByDteTypeCode(999)
        );
    }

    public function test_reconoce_formato_ticket(): void
    {
        $this->insertType(
            code: 39,
            name: 'Boleta Electrónica',
            format: PrintFormat::TICKET_80,
            printParameters: null,
        );

        $configuration =
            $this->repository->findByDteTypeCode(39);

        $this->assertNotNull($configuration);

        $this->assertSame(
            PrintFormat::TICKET_80,
            $configuration->defaultFormat()
        );

        $this->assertTrue(
            $configuration->defaultFormat()->isTicket()
        );
    }

    private function insertType(
        int $code,
        string $name,
        PrintFormat $format,
        ?array $printParameters
    ): int {
        return (int) DB::table(
            'dte_document_types'
        )->insertGetId([
            'code' => $code,
            'name' => $name,
            'is_active' => true,
            'is_printable' => true,
            'default_print_format' => $format->value,
            'print_parameters' => $printParameters !== null
                ? json_encode(
                    $printParameters,
                    JSON_THROW_ON_ERROR
                )
                : null,
        ]);
    }

    private function insertCopy(
        int $typeId,
        PrintCopyType $type,
        bool $isActive,
        int $sortOrder,
        int $defaultQuantity = 1,
        ?string $legend = null
    ): int {
        return (int) DB::table(
            'dte_document_type_print_copies'
        )->insertGetId([
            'dte_document_type_id' => $typeId,
            'code' => $type->value,
            'name' => $type->label(),
            'is_required' => true,
            'default_quantity' => $defaultQuantity,
            'is_active' => $isActive,
            'sort_order' => $sortOrder,
            'legend' => $legend,
            'parameters' => null,
        ]);
    }
}
