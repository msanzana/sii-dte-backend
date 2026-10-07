<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureJwtAccessToken;
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

final class FakeJwtAccessForDteCatalogTest
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

final class StoreDteDocumentTypeCatalogHttpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Conservamos la ruta HTTP real, pero evitamos que
         * este test dependa de generar un JWT auténtico.
         */
        $this->app->instance(
            EnsureJwtAccessToken::class,
            new FakeJwtAccessForDteCatalogTest()
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_post_rechaza_tipo_configurado_pero_no_soportado_con_422(): void
    {
        /*
         * Simulamos:
         *
         * dte_document_types:
         * code      = 110
         * is_active = 1
         *
         * Pero DteType::tryFrom(110) devuelve null.
         */
        $this->bindUseCaseForCatalogValidation(
            dteType: 110,
            existsActive: true,
        );

        $response = $this->postJson(
            '/api/internal/dte/document',
            $this->validPayload(110)
        );

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' =>
                    'No fue posible registrar el documento.',

                'error' =>
                    'El tipo DTE 110 se encuentra configurado, pero todavía no está soportado técnicamente por el backend.',
            ]);
    }

    public function test_post_rechaza_tipo_inexistente_con_422(): void
    {
        /*
         * Simulamos que 999 no existe o está inactivo
         * en dte_document_types.
         */
        $this->bindUseCaseForCatalogValidation(
            dteType: 999,
            existsActive: false,
        );

        $response = $this->postJson(
            '/api/internal/dte/document',
            $this->validPayload(999)
        );

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' =>
                    'No fue posible registrar el documento.',

                'error' =>
                    'El tipo DTE 999 no existe o se encuentra inactivo.',
            ]);
    }

    private function bindUseCaseForCatalogValidation(
        int $dteType,
        bool $existsActive
    ): void {
        /*
         * Estos servicios no deberían ejecutarse porque
         * la validación debe detener el flujo antes del folio.
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
         * No debe crearse ningún documento.
         */
        $documentRepository = Mockery::mock(
            DteDocumentRepositoryInterface::class
        );

        $documentRepository
            ->shouldNotReceive('create');

        /*
         * Empresa válida.
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
         * La autorización falla antes de llegar a ciudad.
         */
        $cityRepository = Mockery::mock(
            CityRepositoryInterface::class
        );

        $cityRepository
            ->shouldNotReceive('existsActiveById');

        $logRepository = Mockery::mock(
            IntegrationLogRepositoryInterface::class
        );

        $logRepository
            ->shouldNotReceive('info');

        /*
         * Sistema DTE habilitado.
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
         * Éste es el comportamiento del catálogo que
         * queremos variar entre 110 y 999.
         */
        $authorizationRepository = Mockery::mock(
            DteTypeAuthorizationRepositoryInterface::class
        );

        $authorizationRepository
            ->shouldReceive('existsActiveTypeByCode')
            ->once()
            ->with($dteType)
            ->andReturn($existsActive);

        /*
         * Para ambos casos el flujo debe detenerse antes
         * de revisar autorizaciones de empresa/sistema:
         *
         * 999 -> falla por catálogo.
         * 110 -> falla por capacidad técnica.
         */
        $authorizationRepository
            ->shouldNotReceive(
                'isAuthorizedForCompany'
            );

        $authorizationRepository
            ->shouldNotReceive(
                'isAuthorizedForSystem'
            );

        $validateDteTypeAuthorizationService =
            new ValidateDteTypeAuthorizationService(
                $authorizationRepository
            );

        /*
         * Folios: absolutamente ninguna operación.
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
         * Caso de uso real.
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
         * El controller utilizará nuestra instancia.
         */
        $this->app->instance(
            CreateDteDocumentUseCase::class,
            $useCase
        );
    }

    private function validPayload(
        int $dteType
    ): array {
        return [
            /*
             * Ya NO existe Rule::in(...) para este campo.
             */
            'dte_type' => $dteType,

            'issue_date' => '2026-10-06',

            /*
             * No enviamos external_system_id.
             *
             * Esto es intencional: proposed_sii_document_type
             * todavía conserva temporalmente su Rule::in(...)
             * y no queremos que interfiera en esta prueba.
             */
            'receiver' => [
                'document' => '76123456-7',
                'name' => 'Receptor Prueba',
                'giro' => 'Comercio',
                'address' => 'Dirección de prueba 123',
                'email' => 'receptor@example.com',
            ],

            'items' => [
                [
                    'name' => 'Producto prueba catálogo',
                    'quantity' => 1,
                    'unit_price' => 1000,

                    /*
                     * El controller actual accede directamente
                     * a estas dos propiedades.
                     */
                    'discount_percent' => 0,
                    'discount_amount' => 0,

                    'tax_exempt' => false,
                ],
            ],
        ];
    }
}