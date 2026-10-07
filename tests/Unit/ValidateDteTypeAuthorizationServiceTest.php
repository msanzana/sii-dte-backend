<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\Services\ValidateDteTypeAuthorizationService;
use App\Modules\Dte\Domain\Exceptions\DteTypeNotAuthorizedException;
use App\Modules\Dte\Domain\RepositoryContracts\DteTypeAuthorizationRepositoryInterface;
use Mockery;
use Tests\TestCase;

final class ValidateDteTypeAuthorizationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_permite_tipo_autorizado_para_empresa_y_sistema(): void
    {
        $repository = Mockery::mock(
            DteTypeAuthorizationRepositoryInterface::class
        );

        $repository
            ->shouldReceive('existsActiveTypeByCode')
            ->once()
            ->with(33)
            ->andReturn(true);

        $repository
            ->shouldReceive('isAuthorizedForCompany')
            ->once()
            ->with(1, 33)
            ->andReturn(true);

        $repository
            ->shouldReceive('isAuthorizedForSystem')
            ->once()
            ->with(1, 1, 33)
            ->andReturn(true);

        $service = new ValidateDteTypeAuthorizationService(
            $repository
        );

        $service->execute(
            companyId: 1,
            externalSystemId: 1,
            dteType: 33,
        );

        $this->addToAssertionCount(1);
    }

    public function test_rechaza_tipo_inexistente_o_inactivo(): void
    {
        $repository = Mockery::mock(
            DteTypeAuthorizationRepositoryInterface::class
        );

        $repository
            ->shouldReceive('existsActiveTypeByCode')
            ->once()
            ->with(99)
            ->andReturn(false);

        $repository
            ->shouldNotReceive('isAuthorizedForCompany');

        $repository
            ->shouldNotReceive('isAuthorizedForSystem');

        $service = new ValidateDteTypeAuthorizationService(
            $repository
        );

        $this->expectException(
            DteTypeNotAuthorizedException::class
        );

        $this->expectExceptionMessage(
            'El tipo DTE 99 no existe o se encuentra inactivo.'
        );

        $service->execute(
            companyId: 1,
            externalSystemId: 1,
            dteType: 99,
        );
    }

    public function test_rechaza_tipo_no_autorizado_para_empresa(): void
    {
        $repository = Mockery::mock(
            DteTypeAuthorizationRepositoryInterface::class
        );

        $repository
            ->shouldReceive('existsActiveTypeByCode')
            ->once()
            ->with(61)
            ->andReturn(true);

        $repository
            ->shouldReceive('isAuthorizedForCompany')
            ->once()
            ->with(1, 61)
            ->andReturn(false);

        $repository
            ->shouldNotReceive('isAuthorizedForSystem');

        $service = new ValidateDteTypeAuthorizationService(
            $repository
        );

        $this->expectException(
            DteTypeNotAuthorizedException::class
        );

        $this->expectExceptionMessage(
            'El tipo DTE 61 no está autorizado para la empresa 1.'
        );

        $service->execute(
            companyId: 1,
            externalSystemId: 1,
            dteType: 61,
        );
    }

    public function test_rechaza_tipo_autorizado_para_empresa_pero_no_para_sistema(): void
    {
        $repository = Mockery::mock(
            DteTypeAuthorizationRepositoryInterface::class
        );

        $repository
            ->shouldReceive('existsActiveTypeByCode')
            ->once()
            ->with(33)
            ->andReturn(true);

        $repository
            ->shouldReceive('isAuthorizedForCompany')
            ->once()
            ->with(1, 33)
            ->andReturn(true);

        $repository
            ->shouldReceive('isAuthorizedForSystem')
            ->once()
            ->with(1, 2, 33)
            ->andReturn(false);

        $service = new ValidateDteTypeAuthorizationService(
            $repository
        );

        $this->expectException(
            DteTypeNotAuthorizedException::class
        );

        $this->expectExceptionMessage(
            'El tipo DTE 33 no está autorizado para la empresa 1 y el sistema externo 2.'
        );

        $service->execute(
            companyId: 1,
            externalSystemId: 2,
            dteType: 33,
        );
    }

    public function test_sin_sistema_externo_solo_exige_autorizacion_de_empresa(): void
    {
        $repository = Mockery::mock(
            DteTypeAuthorizationRepositoryInterface::class
        );

        $repository
            ->shouldReceive('existsActiveTypeByCode')
            ->once()
            ->with(33)
            ->andReturn(true);

        $repository
            ->shouldReceive('isAuthorizedForCompany')
            ->once()
            ->with(1, 33)
            ->andReturn(true);

        $repository
            ->shouldNotReceive('isAuthorizedForSystem');

        $service = new ValidateDteTypeAuthorizationService(
            $repository
        );

        $service->execute(
            companyId: 1,
            externalSystemId: null,
            dteType: 33,
        );

        $this->addToAssertionCount(1);
    }

    public function test_boleta_39_autorizada_para_empresa_y_sistema(): void
    {
        $repository = Mockery::mock(
            DteTypeAuthorizationRepositoryInterface::class
        );

        $repository
            ->shouldReceive('existsActiveTypeByCode')
            ->once()
            ->with(39)
            ->andReturn(true);

        $repository
            ->shouldReceive('isAuthorizedForCompany')
            ->once()
            ->with(1, 39)
            ->andReturn(true);

        $repository
            ->shouldReceive('isAuthorizedForSystem')
            ->once()
            ->with(1, 1, 39)
            ->andReturn(true);

        $service = new ValidateDteTypeAuthorizationService(
            $repository
        );

        $service->execute(
            companyId: 1,
            externalSystemId: 1,
            dteType: 39,
        );

        $this->addToAssertionCount(1);
    }
    public function test_rechaza_tipo_configurado_pero_no_soportado_tecnicamente(): void
    {
        $repository = Mockery::mock(
            DteTypeAuthorizationRepositoryInterface::class
        );

        /*
        * Simulamos que mañana alguien agrega DTE 110
        * al catálogo y lo deja activo.
        */
        $repository
            ->shouldReceive('existsActiveTypeByCode')
            ->once()
            ->with(110)
            ->andReturn(true);

        /*
        * Como el backend todavía no tiene DteType::case
        * para 110, debe detenerse ANTES de consultar
        * autorizaciones.
        */
        $repository
            ->shouldNotReceive('isAuthorizedForCompany');

        $repository
            ->shouldNotReceive('isAuthorizedForSystem');

        $service = new ValidateDteTypeAuthorizationService(
            $repository
        );

        $this->expectException(
            DteTypeNotAuthorizedException::class
        );

        $this->expectExceptionMessage(
            'El tipo DTE 110 se encuentra configurado, pero todavía no está soportado técnicamente por el backend.'
        );

        $service->execute(
            companyId: 1,
            externalSystemId: 1,
            dteType: 110,
        );
    }
}