<?php

namespace Tests\Feature;

use App\Modules\Dte\Application\Services\DteDocumentApplicationService;
use App\Modules\Dte\Application\Services\RecalculateCafCountersService;
use App\Modules\Dte\Application\Services\ResolveReservedFolioForDteService;
use App\Modules\Dte\Application\Services\ValidateDteTypeAuthorizationService;
use App\Modules\Dte\Application\UseCases\Document\CreateDteDocumentUseCase;
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
use Closure;
use Illuminate\Http\Request;
use Mockery;
use ReflectionClass;
use Tests\TestCase;
use App\Http\Middleware\EnsureJwtAccessToken;

/*
|--------------------------------------------------------------------------
| Middleware JWT falso exclusivo para este test
|--------------------------------------------------------------------------
|
| No queremos probar JWT aquí.
|
| Solamente necesitamos reproducir lo que hace el middleware real al dejar
| disponible auth_company_id para el DteDocumentController.
|
*/
final class FakeJwtAccessForDteAuthorizationTest
{
    public function handle(
        Request $request,
        Closure $next
    ): mixed {
        $request->attributes->set(
            'auth_company_id',
            1
        );

        $request->attributes->set(
            'auth_user_id',
            1
        );

        return $next($request);
    }
}

final class StoreDteDocumentTypeAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
        * La ruta mantiene su alias real:
        *
        * jwt.access -> EnsureJwtAccessToken
        *
        * Pero para este test reemplazamos la instancia que
        * Laravel obtiene del contenedor por nuestro middleware falso.
        */
        $this->app->instance(
            EnsureJwtAccessToken::class,
            new FakeJwtAccessForDteAuthorizationTest()
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_post_rechaza_dte_no_autorizado_con_422_sin_tocar_folios(): void
    {
        /*
         * Estos servicios concretos no deben llegar a ejecutarse.
         */
        $documentApplicationService = (
            new ReflectionClass(
                DteDocumentApplicationService::class
            )
        )->newInstanceWithoutConstructor();

        $resolveReservedFolioForDteService = (
            new ReflectionClass(
                ResolveReservedFolioForDteService::class
            )
        )->newInstanceWithoutConstructor();

        $recalculateCafCountersService = (
            new ReflectionClass(
                RecalculateCafCountersService::class
            )
        )->newInstanceWithoutConstructor();

        $validationDomainService =
            new DteDocumentValidationDomainService();

        /*
         * No debe persistirse ningún documento.
         */
        $documentRepository = Mockery::mock(
            DteDocumentRepositoryInterface::class
        );

        $documentRepository
            ->shouldNotReceive('create');

        /*
         * Empresa 1 válida.
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
         * La autorización debe fallar antes de llegar
         * a la validación de ciudad.
         */
        $cityRepository = Mockery::mock(
            CityRepositoryInterface::class
        );

        $cityRepository
            ->shouldNotReceive('existsActiveById');

        /*
         * No debe escribirse log de creación exitosa.
         */
        $logRepository = Mockery::mock(
            IntegrationLogRepositoryInterface::class
        );

        $logRepository
            ->shouldNotReceive('info');

        /*
         * Servicio DTE no pausado.
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
         * DTE 61:
         *
         * - existe en catálogo
         * - está activo
         * - NO está autorizado para empresa 1
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
         * Como falla a nivel empresa, no debemos
         * consultar la autorización del sistema.
         */
        $authorizationRepository
            ->shouldNotReceive(
                'isAuthorizedForSystem'
            );

        $validateDteTypeAuthorizationService =
            new ValidateDteTypeAuthorizationService(
                $authorizationRepository
            );

        /*
         * Ninguna operación sobre folios puede ejecutarse.
         */
        $folioDetailRepository = Mockery::mock(
            FolioDetailRepositoryInterface::class
        );

        $folioDetailRepository
            ->shouldNotReceive(
                'findReservedForDocumentCreationForUpdate'
            );

        $folioDetailRepository
            ->shouldNotReceive(
                'assignToDocument'
            );

        $folioStatusRepository = Mockery::mock(
            FolioStatusRepositoryInterface::class
        );

        $folioStatusRepository
            ->shouldNotReceive(
                'findByCode'
            );

        $folioDetailEventRepository = Mockery::mock(
            FolioDetailEventRepositoryInterface::class
        );

        $folioDetailEventRepository
            ->shouldNotReceive(
                'create'
            );

        $folioReservationRepository = Mockery::mock(
            FolioReservationRepositoryInterface::class
        );

        $folioReservationRepository
            ->shouldNotReceive(
                'advanceCurrentFolio'
            );

        /*
         * Construimos el caso de uso REAL.
         *
         * Usamos argumentos nombrados para evitar depender
         * de la posición actual del constructor.
         */
        $useCase = new CreateDteDocumentUseCase(
            documentApplicationService:
                $documentApplicationService,

            validationDomainService:
                $validationDomainService,

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
         * El controlador de la ruta real pedirá este use case
         * al contenedor Laravel.
         *
         * Le entregamos nuestra instancia controlada.
         */
        $this->app->instance(
            CreateDteDocumentUseCase::class,
            $useCase
        );

        /*
         * Payload válido para StoreDteDocumentRequest.
         *
         * DTE 61 sigue siendo válido para Rule::in(),
         * por lo que debe atravesar la validación HTTP
         * y ser rechazado por nuestra NUEVA autorización.
         */
        $payload = [
            'dte_type' => 61,

            'issue_date' => '2026-10-06',

            'external_system_id' => 1,

            /*
             * required_with:external_system_id
             */
            'proposed_folio' => 999,

            'proposed_sii_document_type' => 61,

            'branch_office_number' => 1,

            'facility_number' => 1,

            'external_branch_code' =>
                'TEST-AUTH-HTTP',

            'receiver' => [
                'document' => '76123456-7',
                'name' => 'Receptor Prueba',
                'giro' => 'Comercio',

                /*
                 * No enviamos city_id para que el FormRequest
                 * no tenga que consultar la tabla cities.
                 */
                'address' => 'Dirección de prueba 123',

                'email' =>
                    'receptor@example.com',
            ],

            'items' => [
                [
                    'name' => 'Producto de prueba',

                    'quantity' => 1,

                    'unit_price' => 1000,

                    /*
                     * Aunque estas propiedades son nullable
                     * en el Request, el controller actualmente
                     * las accede directamente.
                     */
                    'discount_percent' => 0,

                    'discount_amount' => 0,

                    'tax_exempt' => false,
                ],
            ],
        ];

        /*
         * RUTA HTTP REAL.
         */
        $response = $this->postJson(
            '/api/internal/dte/document',
            $payload
        );

        /*
         * El controller captura RuntimeException.
         *
         * DteTypeNotAuthorizedException hereda de nuestra
         * DomainException y ésta de RuntimeException.
         */
        $response
            ->assertStatus(422)
            ->assertJson([
                'message' =>
                    'No fue posible registrar el documento.',

                'error' =>
                    'El tipo DTE 61 no está autorizado para la empresa 1.',
            ]);
    }
}