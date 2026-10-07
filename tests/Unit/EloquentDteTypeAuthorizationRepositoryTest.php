<?php

namespace Tests\Unit;

use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentDteTypeAuthorizationRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class EloquentDteTypeAuthorizationRepositoryTest extends TestCase
{
    private EloquentDteTypeAuthorizationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('dte_type_authorizations');
        Schema::dropIfExists('dte_document_types');

        Schema::create('dte_document_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('code')->unique();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('dte_type_authorizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('external_system_id')->nullable();
            $table->unsignedBigInteger('dte_document_type_id');
            $table->boolean('is_active')->default(true);
        });

        $this->repository =
            new EloquentDteTypeAuthorizationRepository();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('dte_type_authorizations');
        Schema::dropIfExists('dte_document_types');

        parent::tearDown();
    }

    public function test_detecta_tipo_dte_activo_por_codigo(): void
    {
        $typeId = $this->insertType(
            code: 33,
            isActive: true,
        );

        $this->assertGreaterThan(0, $typeId);

        $this->assertTrue(
            $this->repository->existsActiveTypeByCode(33)
        );
    }

    public function test_no_considera_tipo_dte_inactivo_como_activo(): void
    {
        $this->insertType(
            code: 33,
            isActive: false,
        );

        $this->assertFalse(
            $this->repository->existsActiveTypeByCode(33)
        );
    }

    public function test_tipo_dte_inexistente_no_se_considera_activo(): void
    {
        $this->assertFalse(
            $this->repository->existsActiveTypeByCode(99)
        );
    }

    public function test_detecta_autorizacion_activa_a_nivel_empresa(): void
    {
        $typeId = $this->insertType(
            code: 33,
            isActive: true,
        );

        $this->insertAuthorization(
            companyId: 1,
            externalSystemId: null,
            typeId: $typeId,
            isActive: true,
        );

        $this->assertTrue(
            $this->repository->isAuthorizedForCompany(
                companyId: 1,
                dteType: 33,
            )
        );
    }

    public function test_autorizacion_de_sistema_no_reemplaza_autorizacion_de_empresa(): void
    {
        $typeId = $this->insertType(
            code: 33,
            isActive: true,
        );

        $this->insertAuthorization(
            companyId: 1,
            externalSystemId: 1,
            typeId: $typeId,
            isActive: true,
        );

        $this->assertFalse(
            $this->repository->isAuthorizedForCompany(
                companyId: 1,
                dteType: 33,
            )
        );
    }

    public function test_rechaza_autorizacion_de_empresa_desactivada(): void
    {
        $typeId = $this->insertType(
            code: 33,
            isActive: true,
        );

        $this->insertAuthorization(
            companyId: 1,
            externalSystemId: null,
            typeId: $typeId,
            isActive: false,
        );

        $this->assertFalse(
            $this->repository->isAuthorizedForCompany(
                companyId: 1,
                dteType: 33,
            )
        );
    }

    public function test_detecta_autorizacion_activa_para_sistema(): void
    {
        $typeId = $this->insertType(
            code: 39,
            isActive: true,
        );

        $this->insertAuthorization(
            companyId: 1,
            externalSystemId: 1,
            typeId: $typeId,
            isActive: true,
        );

        $this->assertTrue(
            $this->repository->isAuthorizedForSystem(
                companyId: 1,
                externalSystemId: 1,
                dteType: 39,
            )
        );
    }

    public function test_no_acepta_autorizacion_del_mismo_sistema_en_otra_empresa(): void
    {
        $typeId = $this->insertType(
            code: 39,
            isActive: true,
        );

        $this->insertAuthorization(
            companyId: 2,
            externalSystemId: 1,
            typeId: $typeId,
            isActive: true,
        );

        $this->assertFalse(
            $this->repository->isAuthorizedForSystem(
                companyId: 1,
                externalSystemId: 1,
                dteType: 39,
            )
        );
    }

    public function test_rechaza_autorizacion_de_sistema_desactivada(): void
    {
        $typeId = $this->insertType(
            code: 39,
            isActive: true,
        );

        $this->insertAuthorization(
            companyId: 1,
            externalSystemId: 1,
            typeId: $typeId,
            isActive: false,
        );

        $this->assertFalse(
            $this->repository->isAuthorizedForSystem(
                companyId: 1,
                externalSystemId: 1,
                dteType: 39,
            )
        );
    }

    public function test_tipo_inactivo_invalida_autorizacion_de_empresa(): void
    {
        $typeId = $this->insertType(
            code: 33,
            isActive: false,
        );

        $this->insertAuthorization(
            companyId: 1,
            externalSystemId: null,
            typeId: $typeId,
            isActive: true,
        );

        $this->assertFalse(
            $this->repository->isAuthorizedForCompany(
                companyId: 1,
                dteType: 33,
            )
        );
    }

    public function test_tipo_inactivo_invalida_autorizacion_de_sistema(): void
    {
        $typeId = $this->insertType(
            code: 39,
            isActive: false,
        );

        $this->insertAuthorization(
            companyId: 1,
            externalSystemId: 1,
            typeId: $typeId,
            isActive: true,
        );

        $this->assertFalse(
            $this->repository->isAuthorizedForSystem(
                companyId: 1,
                externalSystemId: 1,
                dteType: 39,
            )
        );
    }

    private function insertType(
        int $code,
        bool $isActive
    ): int {
        return (int) DB::table('dte_document_types')
            ->insertGetId([
                'code' => $code,
                'is_active' => $isActive,
            ]);
    }

    private function insertAuthorization(
        int $companyId,
        ?int $externalSystemId,
        int $typeId,
        bool $isActive
    ): int {
        return (int) DB::table('dte_type_authorizations')
            ->insertGetId([
                'company_id' => $companyId,
                'external_system_id' => $externalSystemId,
                'dte_document_type_id' => $typeId,
                'is_active' => $isActive,
            ]);
    }
}