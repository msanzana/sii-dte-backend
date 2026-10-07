<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\DTOs\CreateDteDocumentInputDto;
use App\Modules\Dte\Application\DTOs\ReceiverInputDto;
use App\Modules\Dte\Application\Services\DteDocumentApplicationService;
use App\Modules\Dte\Application\Services\RecalculateCafCountersService;
use App\Modules\Dte\Application\Services\ResolveReservedFolioForDteService;
use App\Modules\Dte\Application\Services\ValidateDteTypeAuthorizationService;
use App\Modules\Dte\Application\UseCases\Document\CreateDteDocumentUseCase;
use App\Modules\Dte\Domain\Exceptions\DteTypeNotAuthorizedException;
use App\Modules\Dte\Domain\RepositoryContracts\CityRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DteTypeAuthorizationRepositoryInterface;
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

final class CreateDteDocumentTypeAuthorizationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_dte_no_autorizado_se_rechaza_antes_de_resolver_o_asignar_folio(): void
    {
        /*
         * Estos servicios no deben llegar a utilizarse porque
         * la autorización debe fallar antes de la transacción.
         *
         * Los instanciamos sin constructor deliberadamente:
         * si el flujo intentara utilizarlos, el test fallaría.
         */
        $documentApplicationService = (
            new ReflectionClass(DteDocumentApplicationService::class)
        )->newInstanceWithoutConstructor();

        $resolveReservedFolioForDteService = (
            new ReflectionClass(ResolveReservedFolioForDteService::class)
        )->newInstanceWithoutConstructor();

        $recalculateCafCountersService = (
            new ReflectionClass(RecalculateCafCountersService::class)
        )->newInstanceWithoutConstructor();

        $validationDomainService =
            new DteDocumentValidationDomainService();

        /*
         * Repositorio de documentos.
         *
         * No debe crearse ningún DTE.
         */
        $documentRepository = Mockery::mock(
            DteDocumentRepositoryInterface::class
        );

        $documentRepository
            ->shouldNotReceive('create');

        /*
         * La empresa sí existe y está activa.
         */
        $companyRepository = Mockery::mock(
            CompanyRepositoryInterface::class
        );

        $companyRepository
            ->shouldReceive('existsActiveById')
            ->once()
            ->with(1)
            ->andReturn(true);

        /*
         * La validación de autorización ocurre antes que
         * cualquier validación posterior de ciudad.
         */
        $cityRepository = Mockery::mock(
            CityRepositoryInterface::class
        );

        $cityRepository
            ->shouldNotReceive('existsActiveById');

        /*
         * No debe generarse log de documento creado.
         */
        $logRepository = Mockery::mock(
            IntegrationLogRepositoryInterface::class
        );

        $logRepository
            ->shouldNotReceive('info');

        /*
         * El servicio no está pausado.
         *
         * CreateDteDocumentUseCase consulta:
         * - estado paused
         * - razón de pausa
         *
         * Devolvemos simplemente el valor por defecto solicitado.
         */
        $settingRepository = Mockery::mock(
            SystemSettingRepositoryInterface::class
        );

        $settingRepository
            ->shouldReceive('get')
            ->twice()
            ->andReturnUsing(
                static fn ($key, $default = null) => $default
            );

        /*
         * Catálogo:
         * DTE 61 existe y está activo.
         *
         * Autorización:
         * empresa 1 NO tiene autorizado DTE 61.
         */
        $authorizationRepository = Mockery::mock(
            DteTypeAuthorizationRepositoryInterface::class
        );

        $authorizationRepository
            ->shouldReceive('existsActiveTypeByCode')
            ->once()
            ->with(61)
            ->andReturn(true);

        $authorizationRepository
            ->shouldReceive('isAuthorizedForCompany')
            ->once()
            ->with(1, 61)
            ->andReturn(false);

        /*
         * Al fallar la autorización de empresa,
         * ni siquiera debe consultar autorización del sistema.
         */
        $authorizationRepository
            ->shouldNotReceive('isAuthorizedForSystem');

        $validateDteTypeAuthorizationService =
            new ValidateDteTypeAuthorizationService(
                $authorizationRepository
            );

        /*
         * Ningún repositorio relacionado con folios
         * debe ser utilizado.
         */
        $folioDetailRepository = Mockery::mock(
            FolioDetailRepositoryInterface::class
        );

        $folioDetailRepository
            ->shouldNotReceive(
                'findReservedForDocumentCreationForUpdate'
            );

        $folioDetailRepository
            ->shouldNotReceive('assignToDocument');

        $folioStatusRepository = Mockery::mock(
            FolioStatusRepositoryInterface::class
        );

        $folioStatusRepository
            ->shouldNotReceive('findByCode');

        $folioDetailEventRepository = Mockery::mock(
            FolioDetailEventRepositoryInterface::class
        );

        $folioDetailEventRepository
            ->shouldNotReceive('create');

        $folioReservationRepository = Mockery::mock(
            FolioReservationRepositoryInterface::class
        );

        $folioReservationRepository
            ->shouldNotReceive('advanceCurrentFolio');

        /*
         * Construimos el caso de uso con las 14 dependencias
         * actuales.
         */
        $useCase = new CreateDteDocumentUseCase(
            documentApplicationService: $documentApplicationService,
            validationDomainService: $validationDomainService,
            documentRepository: $documentRepository,
            companyRepository: $companyRepository,
            cityRepository: $cityRepository,
            logRepository: $logRepository,
            settingRepository: $settingRepository,

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
                $recalculateCafCountersService,

            validateDteTypeAuthorizationService:
                $validateDteTypeAuthorizationService,
        );
        /*
         * Ponemos external_system_id y proposed_folio
         * intencionalmente.
         *
         * Si el flujo llegara a ResolveReservedFolioForDteService,
         * intentaría procesar ese folio y el test fallaría.
         */
        $input = new CreateDteDocumentInputDto(
            companyId: 1,
            dteType: 61,
            issueDate: '2026-10-06',

            receiver: new ReceiverInputDto(
                document: '76123456-7',
                name: 'Receptor Prueba',
                giro: null,
                address: null,
                cityId: null,
                email: null,
            ),

            items: [],

            references: [],

            externalId:
                'TEST-DTE-NO-AUTORIZADO-61',

            headerPayload: null,
            rawInput: null,

            externalSystemId: 1,

            proposedFolio: 999,
            proposedSiiDocumentType: 61,

            branchOfficeNumber: 1,
            facilityNumber: 1,

            externalBranchCode:
                'TEST-AUTH',
        );

        $this->expectException(
            DteTypeNotAuthorizedException::class
        );

        $this->expectExceptionMessage(
            'El tipo DTE 61 no está autorizado para la empresa 1.'
        );

        $useCase->execute($input);
    }
}