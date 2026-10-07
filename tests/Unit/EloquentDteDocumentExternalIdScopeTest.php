<?php

namespace Tests\Unit;

use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\DteDocumentEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\DteDocumentPersistenceMapper;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentDteDocumentRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class EloquentDteDocumentExternalIdScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('dte_documents', function (Blueprint $table): void {
            $table->id();

            $table->uuid('external_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('external_system_id')->nullable();

            $table->unsignedSmallInteger('dte_type')->default(33);
            $table->date('issue_date')->default('2026-09-28');
            $table->string('status', 40)->default('draft');

            $table->string('receiver_document', 20)->default('66666666-6');
            $table->string('receiver_name', 120)->default('Receptor');

            $table->decimal('net_amount', 18, 2)->default(0);
            $table->decimal('exempt_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);

            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('dte_documents');

        parent::tearDown();
    }

    public function test_detecta_external_id_en_la_misma_empresa_y_sistema(): void
    {
        $externalId = '11111111-1111-4111-8111-111111111111';

        DteDocumentEloquentModel::query()->create([
            'external_id' => $externalId,
            'company_id' => 1,
            'external_system_id' => 10,
        ]);

        $repository = $this->repository();

        $this->assertTrue(
            $repository->existsByExternalIdInScope(
                companyId: 1,
                externalSystemId: 10,
                externalId: $externalId
            )
        );
    }

    public function test_mismo_external_id_en_otra_empresa_no_es_duplicado(): void
    {
        $externalId = '22222222-2222-4222-8222-222222222222';

        DteDocumentEloquentModel::query()->create([
            'external_id' => $externalId,
            'company_id' => 1,
            'external_system_id' => 10,
        ]);

        $repository = $this->repository();

        $this->assertFalse(
            $repository->existsByExternalIdInScope(
                companyId: 2,
                externalSystemId: 10,
                externalId: $externalId
            )
        );
    }

    public function test_mismo_external_id_en_otro_sistema_de_la_misma_empresa_no_es_duplicado(): void
    {
        $externalId = '33333333-3333-4333-8333-333333333333';

        DteDocumentEloquentModel::query()->create([
            'external_id' => $externalId,
            'company_id' => 1,
            'external_system_id' => 10,
        ]);

        $repository = $this->repository();

        $this->assertFalse(
            $repository->existsByExternalIdInScope(
                companyId: 1,
                externalSystemId: 20,
                externalId: $externalId
            )
        );
    }

    private function repository(): EloquentDteDocumentRepository
    {
        return new EloquentDteDocumentRepository(
            app(DteDocumentPersistenceMapper::class)
        );
    }
    public function test_detecta_external_id_repetido_en_el_ambito_interno_de_la_misma_empresa(): void
    {
        $externalId =
            '44444444-4444-4444-8444-444444444444';

        DteDocumentEloquentModel::query()->create([
            'external_id' =>
                $externalId,

            'company_id' =>
                1,

            'external_system_id' =>
                null,
        ]);

        $repository =
            $this->repository();

        $this->assertTrue(
            $repository->existsByExternalIdInScope(
                companyId:
                    1,

                externalSystemId:
                    null,

                externalId:
                    $externalId
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Otra empresa sigue siendo otro ámbito
        |--------------------------------------------------------------------------
        */

        $this->assertFalse(
            $repository->existsByExternalIdInScope(
                companyId:
                    2,

                externalSystemId:
                    null,

                externalId:
                    $externalId
            )
        );
    }
}