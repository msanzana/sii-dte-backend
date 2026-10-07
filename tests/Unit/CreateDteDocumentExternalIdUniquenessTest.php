<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\DTOs\CreateDteDocumentInputDto;
use App\Modules\Dte\Application\DTOs\ReceiverInputDto;
use App\Modules\Dte\Application\Services\DteDocumentApplicationService;
use App\Modules\Dte\Application\Services\RecalculateCafCountersService;
use App\Modules\Dte\Application\Services\ResolveReservedFolioForDteService;
use App\Modules\Dte\Application\Services\ValidateExternalSystemAccessService;
use App\Modules\Dte\Application\UseCases\Document\CreateDteDocumentUseCase;
use App\Modules\Dte\Domain\Exceptions\DomainException;
use App\Modules\Dte\Domain\Exceptions\DuplicateExternalIdException;
use App\Modules\Dte\Domain\RepositoryContracts\CityRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\ExternalSystemRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailEventRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioReservationRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioStatusRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SystemSettingRepositoryInterface;
use App\Modules\Dte\Domain\Services\DteDocumentValidationDomainService;
use Mockery;
use ReflectionClass;
use Tests\TestCase;
use App\Modules\Dte\Application\Services\ValidateDteTypeAuthorizationService;
use App\Modules\Dte\Domain\RepositoryContracts\DteTypeAuthorizationRepositoryInterface;
final class CreateDteDocumentExternalIdUniquenessTest extends TestCase
{
    public function test_external_id_duplicado_se_rechaza_antes_de_reservar_o_asignar_folio(): void
    {
        $externalId =
            'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

        /*
        |--------------------------------------------------------------------------
        | Configuración general
        |--------------------------------------------------------------------------
        */

        $settingRepository =
            Mockery::mock(
                SystemSettingRepositoryInterface::class
            );

        $settingRepository
            ->shouldReceive('get')
            ->twice()
            ->andReturn(
                false,
                null
            );

        $companyRepository =
            Mockery::mock(
                CompanyRepositoryInterface::class
            );

        $companyRepository
            ->shouldReceive('existsActiveById')
            ->once()
            ->with(1)
            ->andReturnTrue();

        $cityRepository =
            Mockery::mock(
                CityRepositoryInterface::class
            );

        $cityRepository
            ->shouldNotReceive(
                'existsActiveById'
            );

        /*
        |--------------------------------------------------------------------------
        | Repositorio DTE
        |--------------------------------------------------------------------------
        |
        | El external_id ya existe exactamente en:
        |
        | company_id         = 1
        | external_system_id = 10
        |
        */

        $documentRepository =
            Mockery::mock(
                DteDocumentRepositoryInterface::class
            );

        $documentRepository
            ->shouldReceive(
                'existsByExternalIdInScope'
            )
            ->once()
            ->with(
                1,
                10,
                $externalId
            )
            ->andReturnTrue();

        $documentRepository
            ->shouldNotReceive(
                'create'
            );

        /*
        |--------------------------------------------------------------------------
        | Folios / sistema externo
        |--------------------------------------------------------------------------
        |
        | Si el guard de external_id funciona en el lugar correcto,
        | jamás debemos llegar a consultar el sistema externo ni reservar
        | o buscar un folio.
        |
        */

        $externalSystemRepository =
            Mockery::mock(
                ExternalSystemRepositoryInterface::class
            );

        $externalSystemRepository
            ->shouldNotReceive(
                'findByCompanyAndId'
            );

        $folioDetailRepository =
            Mockery::mock(
                FolioDetailRepositoryInterface::class
            );

        $folioDetailRepository
            ->shouldNotReceive(
                'findReservedForDocumentCreationForUpdate'
            );

        $resolveReservedFolioForDteService =
            new ResolveReservedFolioForDteService(
                validateExternalSystemAccessService:
                    new ValidateExternalSystemAccessService(
                        externalSystemRepository:
                            $externalSystemRepository
                    ),

                folioDetailRepository:
                    $folioDetailRepository
            );

        /*
        |--------------------------------------------------------------------------
        | Dependencias que tampoco deben alcanzarse
        |--------------------------------------------------------------------------
        */

        $folioStatusRepository =
            Mockery::mock(
                FolioStatusRepositoryInterface::class
            );

        $folioStatusRepository
            ->shouldNotReceive(
                'findByCode'
            );

        $folioDetailEventRepository =
            Mockery::mock(
                FolioDetailEventRepositoryInterface::class
            );

        $folioDetailEventRepository
            ->shouldNotReceive(
                'create'
            );

        $folioReservationRepository =
            Mockery::mock(
                FolioReservationRepositoryInterface::class
            );

        $folioReservationRepository
            ->shouldNotReceive(
                'advanceCurrentFolio'
            );

        $logRepository =
            Mockery::mock(
                IntegrationLogRepositoryInterface::class
            );

        $logRepository
            ->shouldNotReceive(
                'info'
            );
        $dteTypeAuthorizationRepository = \Mockery::mock(
            DteTypeAuthorizationRepositoryInterface::class
        );

        $dteTypeAuthorizationRepository
            ->shouldReceive('existsActiveTypeByCode')
            ->andReturn(true);

        $dteTypeAuthorizationRepository
            ->shouldReceive('isAuthorizedForCompany')
            ->andReturn(true);

        $dteTypeAuthorizationRepository
            ->shouldReceive('isAuthorizedForSystem')
            ->andReturn(true);

        $validateDteTypeAuthorizationService =
            new ValidateDteTypeAuthorizationService(
                $dteTypeAuthorizationRepository
            );
        /*
        |--------------------------------------------------------------------------
        | Caso de uso
        |--------------------------------------------------------------------------
        */

        $useCase =
            new CreateDteDocumentUseCase(
                documentApplicationService:
                    $this->withoutConstructor(
                        DteDocumentApplicationService::class
                    ),

                validationDomainService:
                    $this->withoutConstructor(
                        DteDocumentValidationDomainService::class
                    ),

                documentRepository:
                    $documentRepository,

                companyRepository:
                    $companyRepository,

                cityRepository:
                    $cityRepository,

                logRepository:
                    $logRepository,

                settingRepository:
                    $settingRepository,
                    
                validateDteTypeAuthorizationService:
                    $validateDteTypeAuthorizationService,

                resolveReservedFolioForDteService:
                    $resolveReservedFolioForDteService,

                folioDetailRepository:
                    $folioDetailRepository,

                folioStatusRepository:
                    $folioStatusRepository,

                folioDetailEventRepository:
                    $folioDetailEventRepository,

                folioReservationRepository:
                    $folioReservationRepository,

                recalculateCafCountersService:
                    $this->withoutConstructor(
                        RecalculateCafCountersService::class
                    )
            );

        /*
        |--------------------------------------------------------------------------
        | Input
        |--------------------------------------------------------------------------
        */

        $input =
            new CreateDteDocumentInputDto(
                companyId:
                    1,

                dteType:
                    33,

                issueDate:
                    '2026-09-29',

                receiver:
                    new ReceiverInputDto(
                        document:
                            '66666666-6',

                        name:
                            'Receptor Test'
                    ),

                items:
                    [],

                externalId:
                    $externalId,

                externalSystemId:
                    10,

                proposedFolio:
                    77,

                proposedSiiDocumentType:
                    33
            );

        /*
        |--------------------------------------------------------------------------
        | Ejecución
        |--------------------------------------------------------------------------
        */

        try {
            $useCase->execute(
                $input
            );

            $this->fail(
                'Se esperaba DuplicateExternalIdException.'
            );

        } catch (
            DomainException $exception
        ) {
            $this->assertSame(
                DuplicateExternalIdException::class,
                $exception::class
            );

            $this->assertStringContainsString(
                $externalId,
                $exception->getMessage()
            );
        }
    }

    private function withoutConstructor(
        string $class
    ): object {
        return (new ReflectionClass($class))
            ->newInstanceWithoutConstructor();
    }
}