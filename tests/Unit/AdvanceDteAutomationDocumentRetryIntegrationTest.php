<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\DTOs\AdvanceDteAutomationInputDto;
use App\Modules\Dte\Application\Exceptions\DocumentAutomationRetryScheduledException;
use App\Modules\Dte\Application\Services\DteTedBuildDomainService;
use App\Modules\Dte\Application\Services\HandleDocumentAutomationFailureService;
use App\Modules\Dte\Application\Services\LoadCertificateMaterialForEmisionService;
use App\Modules\Dte\Application\Services\ScheduleDocumentAutomationRetryService;
use App\Modules\Dte\Application\UseCases\Automation\AdvanceDteAutomationUseCase;
use App\Modules\Dte\Application\UseCases\Dispatch\SendSignedBoletaToSiiUseCase;
use App\Modules\Dte\Application\UseCases\Dispatch\SendSignedDteToSiiUseCase;
use App\Modules\Dte\Application\UseCases\Document\BuildDteXmlUseCase;
use App\Modules\Dte\Application\UseCases\Document\BuildTedUseCase;
use App\Modules\Dte\Application\UseCases\Document\PrepareDteDocumentForXmlUseCase;
use App\Modules\Dte\Application\UseCases\Document\SignDteXmlUseCase;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Entities\DteLineItem;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiCafRepositoryInterface;
use App\Modules\Dte\Domain\Services\DteAutomationPlannerService;
use App\Modules\Dte\Domain\Services\DteXmlSignDomainService;
use App\Modules\Dte\Domain\Services\TedDataAssemblerService;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use App\Modules\Dte\Infrastructure\Storage\DtePrivateStorageService;
use App\Modules\Dte\Infrastructure\Xml\CafTedMaterialExtractorService;
use App\Modules\Dte\Infrastructure\Xml\DteTedEmbedderService;
use App\Modules\Dte\Infrastructure\Xml\DteXmlSignatureService;
use App\Modules\Dte\Infrastructure\Xml\TedSignatureService;
use App\Modules\Dte\Infrastructure\Xml\TedXmlBuilderService;
use Mockery;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;
use App\Modules\Dte\Application\Exceptions\DocumentAutomationRetryExhaustedException;
use App\Modules\Dte\Domain\Services\DteDocumentPreparationDomainService;
use App\Modules\Dte\Application\Exceptions\DocumentAutomationNotRetryableException;
use App\Modules\Dte\Domain\Exceptions\InvalidDteException;
final class AdvanceDteAutomationDocumentRetryIntegrationTest
    extends TestCase
{
    public function test_fallo_de_build_xml_programa_retry_y_lanza_excepcion_controlada(): void
    {
        $this->configurarRetry();

        $document =
            $this->documento();

        $failure =
            new RuntimeException(
                'Fallo controlado construyendo XML.'
            );

        /*
        |--------------------------------------------------------------------------
        | Repositorio
        |--------------------------------------------------------------------------
        */

        $repository =
            Mockery::mock(
                DteDocumentRepositoryInterface::class
            );

        /*
        | Lectura inicial del orquestador.
        */

        $repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(
                $document
            );

        /*
        | En este escenario BuildDteXmlUseCase es mockeado directamente,
        | por lo que este lock corresponde al handler posterior al fallo.
        */

        $repository
            ->shouldReceive('findByIdForUpdate')
            ->once()
            ->with(1)
            ->andReturn(
                $document
            );

        /*
        | El retry debe quedar persistido conservando las referencias
        | jurídicas y de negocio del DTE.
        */

        $repository
            ->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(
                    function (
                        DteDocument $updated
                    ): bool {
                        return
                            $updated->status()
                                === DteStatus::FOLIO_ASSIGNED->value

                            && $updated->automationRetryAction()
                                === 'build_xml'

                            && $updated->automationRetryCount()
                                === 1

                            && $updated->automationNextRetryAt()
                                !== null

                            && $updated->lastErrorCode()
                                === 'AUTOMATION_BUILD_XML_FAILED'

                            && $updated->lastErrorMessage()
                                === 'Fallo controlado construyendo XML.'

                            && $updated->folio()
                                === 77

                            && $updated->cafId()
                                === 15

                            && $updated->folioReservationId()
                                === 21

                            && $updated->externalSystemId()
                                === 1;
                    }
                )
            )
            ->andReturnUsing(
                fn (
                    DteDocument $updated
                ): DteDocument =>
                    $updated
            );

        /*
        |--------------------------------------------------------------------------
        | build_xml falla
        |--------------------------------------------------------------------------
        */

        $buildDteXmlUseCase =
            Mockery::mock(
                BuildDteXmlUseCase::class
            );

        $buildDteXmlUseCase
            ->shouldReceive('execute')
            ->once()
            ->andThrow(
                $failure
            );

        /*
        |--------------------------------------------------------------------------
        | Handler real
        |--------------------------------------------------------------------------
        */

        $failureHandler =
            new HandleDocumentAutomationFailureService(
                documentRepository:
                    $repository,

                scheduleRetryService:
                    new ScheduleDocumentAutomationRetryService()
            );

        /*
        |--------------------------------------------------------------------------
        | Orquestador
        |--------------------------------------------------------------------------
        */

        $useCase =
            new AdvanceDteAutomationUseCase(
                documentRepository:
                    $repository,

                automationPlannerService:
                    new DteAutomationPlannerService(),

                prepareDteDocumentForXmlUseCase:
                    $this->withoutConstructor(
                        PrepareDteDocumentForXmlUseCase::class
                    ),

                buildDteXmlUseCase:
                    $buildDteXmlUseCase,

                buildTedUseCase:
                    $this->withoutConstructor(
                        BuildTedUseCase::class
                    ),

                signDteXmlUseCase:
                    $this->withoutConstructor(
                        SignDteXmlUseCase::class
                    ),

                sendSignedDteToSiiUseCase:
                    $this->withoutConstructor(
                        SendSignedDteToSiiUseCase::class
                    ),

                sendSignedBoletaToSiiUseCase:
                    $this->withoutConstructor(
                        SendSignedBoletaToSiiUseCase::class
                    ),

                handleDocumentAutomationFailureService:
                    $failureHandler
            );

        /*
        |--------------------------------------------------------------------------
        | Ejecutar
        |--------------------------------------------------------------------------
        */

        try {
            $useCase->execute(
                new AdvanceDteAutomationInputDto(
                    documentId:
                        1
                )
            );

            $this->fail(
                'Se esperaba DocumentAutomationRetryScheduledException.'
            );

        } catch (
            DocumentAutomationRetryScheduledException $exception
        ) {
            $this->assertSame(
                1,
                $exception->documentId()
            );

            $this->assertSame(
                'build_xml',
                $exception->action()
            );

            $this->assertSame(
                $failure,
                $exception->getPrevious()
            );

            $this->assertSame(
                'Fallo controlado construyendo XML.',
                $exception->getMessage()
            );
        }
    }

    public function test_fallo_de_build_ted_programa_retry_y_lanza_excepcion_controlada(): void
    {
        $this->configurarRetry();

        /*
        |--------------------------------------------------------------------------
        | Documento en XML_BUILT
        |--------------------------------------------------------------------------
        |
        | BuildTedUseCase real valida que exista al menos una línea de
        | detalle, además del folio y del XML base.
        |
        */

        $document =
            $this->documento(
                items: [
                    new DteLineItem(
                        lineNumber:
                            1,

                        itemCodeType:
                            null,

                        itemCode:
                            null,

                        name:
                            'Producto prueba',

                        description:
                            null,

                        quantity:
                            1,

                        unitPrice:
                            1000,

                        discountPercent:
                            0,

                        discountAmount:
                            0,

                        taxExempt:
                            false,

                        lineAmount:
                            1000
                    ),
                ]
            )
                ->withUnsignedXmlBuilt(
                    'xml/dte_base_test.xml'
                );

        $failure =
            new RuntimeException(
                'Fallo controlado construyendo TED.'
            );

        /*
        |--------------------------------------------------------------------------
        | Repositorio
        |--------------------------------------------------------------------------
        */

        $repository =
            Mockery::mock(
                DteDocumentRepositoryInterface::class
            );

        /*
        | Lectura inicial del orquestador.
        */

        $repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(
                $document
            );

        /*
        | Aquí sí esperamos DOS locks:
        |
        | 1. BuildTedUseCase real entra en su DB::transaction().
        | 2. Después del fallo/rollback, el handler abre otra transacción.
        */

        $repository
            ->shouldReceive('findByIdForUpdate')
            ->twice()
            ->with(1)
            ->andReturn(
                $document,
                $document
            );

        /*
        | El retry debe conservar el XML base que ya era válido.
        */

        $repository
            ->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(
                    function (
                        DteDocument $updated
                    ): bool {
                        return
                            $updated->status()
                                === DteStatus::XML_BUILT->value

                            && $updated->automationRetryAction()
                                === 'build_ted'

                            && $updated->automationRetryCount()
                                === 1

                            && $updated->automationNextRetryAt()
                                !== null

                            && $updated->lastErrorCode()
                                === 'AUTOMATION_BUILD_TED_FAILED'

                            && $updated->lastErrorMessage()
                                === 'Fallo controlado construyendo TED.'

                            && $updated->unsignedXmlPath()
                                === 'xml/dte_base_test.xml'

                            && $updated->folio()
                                === 77

                            && $updated->cafId()
                                === 15

                            && $updated->folioReservationId()
                                === 21

                            && $updated->externalSystemId()
                                === 1;
                    }
                )
            )
            ->andReturnUsing(
                fn (
                    DteDocument $updated
                ): DteDocument =>
                    $updated
            );

        /*
        |--------------------------------------------------------------------------
        | Fallo controlado dentro de BuildTedUseCase REAL
        |--------------------------------------------------------------------------
        |
        | La clase es final, así que no mockeamos execute().
        |
        | Permitimos que:
        |
        | - entre en DB::transaction();
        | - bloquee el documento;
        | - valide XML_BUILT;
        |
        | y hacemos fallar la consulta de la empresa inmediatamente después.
        |
        | Esto evita llegar al CAF, archivos, TED o storage.
        |
        */

        $companyRepository =
            Mockery::mock(
                CompanyRepositoryInterface::class
            );

        $companyRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andThrow(
                $failure
            );

        /*
        |--------------------------------------------------------------------------
        | BuildTedUseCase REAL
        |--------------------------------------------------------------------------
        */

        $buildTedUseCase =
            new BuildTedUseCase(
                documentRepository:
                    $repository,

                companyRepository:
                    $companyRepository,

                cafRepository:
                    Mockery::mock(
                        SiiCafRepositoryInterface::class
                    ),

                logRepository:
                    Mockery::mock(
                        IntegrationLogRepositoryInterface::class
                    ),

                tedBuildDomainService:
                    new DteTedBuildDomainService(),

                tedDataAssemblerService:
                    $this->withoutConstructor(
                        TedDataAssemblerService::class
                    ),

                cafTedMaterialExtractorService:
                    $this->withoutConstructor(
                        CafTedMaterialExtractorService::class
                    ),

                tedXmlBuilderService:
                    $this->withoutConstructor(
                        TedXmlBuilderService::class
                    ),

                tedSignatureService:
                    $this->withoutConstructor(
                        TedSignatureService::class
                    ),

                dteTedEmbedderService:
                    $this->withoutConstructor(
                        DteTedEmbedderService::class
                    ),

                storageService:
                    $this->withoutConstructor(
                        DtePrivateStorageService::class
                    ),
            );

        /*
        |--------------------------------------------------------------------------
        | Handler real
        |--------------------------------------------------------------------------
        */

        $failureHandler =
            new HandleDocumentAutomationFailureService(
                documentRepository:
                    $repository,

                scheduleRetryService:
                    new ScheduleDocumentAutomationRetryService()
            );

        /*
        |--------------------------------------------------------------------------
        | Orquestador
        |--------------------------------------------------------------------------
        */

        $useCase =
            new AdvanceDteAutomationUseCase(
                documentRepository:
                    $repository,

                automationPlannerService:
                    new DteAutomationPlannerService(),

                prepareDteDocumentForXmlUseCase:
                    $this->withoutConstructor(
                        PrepareDteDocumentForXmlUseCase::class
                    ),

                buildDteXmlUseCase:
                    $this->withoutConstructor(
                        BuildDteXmlUseCase::class
                    ),

                buildTedUseCase:
                    $buildTedUseCase,

                signDteXmlUseCase:
                    $this->withoutConstructor(
                        SignDteXmlUseCase::class
                    ),

                sendSignedDteToSiiUseCase:
                    $this->withoutConstructor(
                        SendSignedDteToSiiUseCase::class
                    ),

                sendSignedBoletaToSiiUseCase:
                    $this->withoutConstructor(
                        SendSignedBoletaToSiiUseCase::class
                    ),

                handleDocumentAutomationFailureService:
                    $failureHandler
            );

        /*
        |--------------------------------------------------------------------------
        | Ejecutar
        |--------------------------------------------------------------------------
        */

        try {
            $useCase->execute(
                new AdvanceDteAutomationInputDto(
                    documentId:
                        1
                )
            );

            $this->fail(
                'Se esperaba DocumentAutomationRetryScheduledException.'
            );

        } catch (
            DocumentAutomationRetryScheduledException $exception
        ) {
            $this->assertSame(
                1,
                $exception->documentId()
            );

            $this->assertSame(
                'build_ted',
                $exception->action()
            );

            $this->assertSame(
                $failure,
                $exception->getPrevious()
            );

            $this->assertSame(
                'Fallo controlado construyendo TED.',
                $exception->getMessage()
            );
        }
    }

    public function test_fallo_de_sign_xml_programa_retry_y_lanza_excepcion_controlada(): void
    {
        $this->configurarRetry();

        /*
        |--------------------------------------------------------------------------
        | Documento en TED_BUILT
        |--------------------------------------------------------------------------
        */

        $document =
            $this->documento()
                ->withUnsignedXmlBuilt(
                    'xml/dte_base_test.xml'
                )
                ->withTedBuilt(
                    tedXml:
                        '<TED>TEST</TED>',

                    unsignedXmlPath:
                        'xml/dte_ted_test.xml'
                );

        $failure =
            new RuntimeException(
                'Fallo controlado firmando XML.'
            );

        /*
        |--------------------------------------------------------------------------
        | Repositorio
        |--------------------------------------------------------------------------
        */

        $repository =
            Mockery::mock(
                DteDocumentRepositoryInterface::class
            );

        /*
        | Lectura inicial del orquestador.
        */

        $repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(
                $document
            );

        /*
        | Dos locks:
        |
        | 1. SignDteXmlUseCase real.
        | 2. Handler del retry después del rollback.
        */

        $repository
            ->shouldReceive('findByIdForUpdate')
            ->twice()
            ->with(1)
            ->andReturn(
                $document,
                $document
            );

        /*
        | Al fallar sign_xml debemos conservar tanto el XML con TED como
        | todas las referencias del documento.
        */

        $repository
            ->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(
                    function (
                        DteDocument $updated
                    ): bool {
                        return
                            $updated->status()
                                === DteStatus::TED_BUILT->value

                            && $updated->automationRetryAction()
                                === 'sign_xml'

                            && $updated->automationRetryCount()
                                === 1

                            && $updated->automationNextRetryAt()
                                !== null

                            && $updated->lastErrorCode()
                                === 'AUTOMATION_SIGN_XML_FAILED'

                            && $updated->lastErrorMessage()
                                === 'Fallo controlado firmando XML.'

                            && $updated->tedXml()
                                === '<TED>TEST</TED>'

                            && $updated->unsignedXmlPath()
                                === 'xml/dte_ted_test.xml'

                            && $updated->folio()
                                === 77

                            && $updated->cafId()
                                === 15

                            && $updated->folioReservationId()
                                === 21

                            && $updated->externalSystemId()
                                === 1;
                    }
                )
            )
            ->andReturnUsing(
                fn (
                    DteDocument $updated
                ): DteDocument =>
                    $updated
            );

        /*
        |--------------------------------------------------------------------------
        | Fallo controlado dentro de SignDteXmlUseCase REAL
        |--------------------------------------------------------------------------
        |
        | Permitimos:
        |
        | - DB::transaction();
        | - findByIdForUpdate();
        | - validación TED_BUILT;
        |
        | y fallamos al consultar la empresa.
        |
        | Por tanto todavía no cargamos certificado, no leemos archivos y
        | no ejecutamos ninguna operación criptográfica.
        |
        */

        $companyRepository =
            Mockery::mock(
                CompanyRepositoryInterface::class
            );

        $companyRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andThrow(
                $failure
            );

        /*
        |--------------------------------------------------------------------------
        | SignDteXmlUseCase REAL
        |--------------------------------------------------------------------------
        */

        $signDteXmlUseCase =
            new SignDteXmlUseCase(
                documentRepository:
                    $repository,

                companyRepository:
                    $companyRepository,

                logRepository:
                    Mockery::mock(
                        IntegrationLogRepositoryInterface::class
                    ),

                dteXmlSignatureService:
                    $this->withoutConstructor(
                        DteXmlSignatureService::class
                    ),

                storageService:
                    $this->withoutConstructor(
                        DtePrivateStorageService::class
                    ),

                xmlSignDomainService:
                    new DteXmlSignDomainService(),

                loadCertificateMaterialForEmisionService:
                    $this->withoutConstructor(
                        LoadCertificateMaterialForEmisionService::class
                    ),
            );

        /*
        |--------------------------------------------------------------------------
        | Handler real
        |--------------------------------------------------------------------------
        */

        $failureHandler =
            new HandleDocumentAutomationFailureService(
                documentRepository:
                    $repository,

                scheduleRetryService:
                    new ScheduleDocumentAutomationRetryService()
            );

        /*
        |--------------------------------------------------------------------------
        | Orquestador
        |--------------------------------------------------------------------------
        */

        $useCase =
            new AdvanceDteAutomationUseCase(
                documentRepository:
                    $repository,

                automationPlannerService:
                    new DteAutomationPlannerService(),

                prepareDteDocumentForXmlUseCase:
                    $this->withoutConstructor(
                        PrepareDteDocumentForXmlUseCase::class
                    ),

                buildDteXmlUseCase:
                    $this->withoutConstructor(
                        BuildDteXmlUseCase::class
                    ),

                buildTedUseCase:
                    $this->withoutConstructor(
                        BuildTedUseCase::class
                    ),

                signDteXmlUseCase:
                    $signDteXmlUseCase,

                sendSignedDteToSiiUseCase:
                    $this->withoutConstructor(
                        SendSignedDteToSiiUseCase::class
                    ),

                sendSignedBoletaToSiiUseCase:
                    $this->withoutConstructor(
                        SendSignedBoletaToSiiUseCase::class
                    ),

                handleDocumentAutomationFailureService:
                    $failureHandler
            );

        /*
        |--------------------------------------------------------------------------
        | Ejecutar
        |--------------------------------------------------------------------------
        */

        try {
            $useCase->execute(
                new AdvanceDteAutomationInputDto(
                    documentId:
                        1
                )
            );

            $this->fail(
                'Se esperaba DocumentAutomationRetryScheduledException.'
            );

        } catch (
            DocumentAutomationRetryScheduledException $exception
        ) {
            $this->assertSame(
                1,
                $exception->documentId()
            );

            $this->assertSame(
                'sign_xml',
                $exception->action()
            );

            $this->assertSame(
                $failure,
                $exception->getPrevious()
            );

            $this->assertSame(
                'Fallo controlado firmando XML.',
                $exception->getMessage()
            );
        }
    }
public function test_fallo_del_ultimo_retry_de_build_xml_lanza_excepcion_de_agotamiento(): void
{
    $this->configurarRetry();

    /*
    |--------------------------------------------------------------------------
    | Documento con los 3 retries ya consumidos
    |--------------------------------------------------------------------------
    */

    $document =
        $this->documento()
            ->withAutomationRetryScheduled(
                action:
                    'build_xml',

                nextRetryAt:
                    '2026-09-27 10:00:00',

                errorCode:
                    'AUTOMATION_BUILD_XML_FAILED',

                errorMessage:
                    'Fallo 1'
            )
            ->withAutomationRetryScheduled(
                action:
                    'build_xml',

                nextRetryAt:
                    '2026-09-27 10:02:00',

                errorCode:
                    'AUTOMATION_BUILD_XML_FAILED',

                errorMessage:
                    'Fallo 2'
            )
            ->withAutomationRetryScheduled(
                action:
                    'build_xml',

                nextRetryAt:
                    '2026-09-27 10:05:00',

                errorCode:
                    'AUTOMATION_BUILD_XML_FAILED',

                errorMessage:
                    'Fallo 3'
            );

    $failure =
        new RuntimeException(
            'Falló definitivamente build_xml.'
        );

    /*
    |--------------------------------------------------------------------------
    | Repositorio
    |--------------------------------------------------------------------------
    */

    $repository =
        Mockery::mock(
            DteDocumentRepositoryInterface::class
        );

    $repository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn(
            $document
        );

    /*
    | Sólo corresponde al handler, porque BuildDteXmlUseCase será mockeado.
    */

    $repository
        ->shouldReceive('findByIdForUpdate')
        ->once()
        ->with(1)
        ->andReturn(
            $document
        );

    /*
    | Debe persistir el estado terminal:
    |
    | count = 3
    | next_retry_at = null
    */

    $repository
        ->shouldReceive('update')
        ->once()
        ->with(
            Mockery::on(
                function (
                    DteDocument $updated
                ): bool {
                    return
                        $updated->status()
                            === DteStatus::FOLIO_ASSIGNED->value

                        && $updated->automationRetryAction()
                            === 'build_xml'

                        && $updated->automationRetryCount()
                            === 3

                        && $updated->automationNextRetryAt()
                            === null

                        && $updated->lastErrorCode()
                            === 'AUTOMATION_BUILD_XML_RETRY_EXHAUSTED'

                        && str_contains(
                            (string) $updated->lastErrorMessage(),
                            'Falló definitivamente build_xml.'
                        )

                        && $updated->folio()
                            === 77

                        && $updated->cafId()
                            === 15

                        && $updated->folioReservationId()
                            === 21

                        && $updated->externalSystemId()
                            === 1;
                }
            )
        )
        ->andReturnUsing(
            fn (
                DteDocument $updated
            ): DteDocument =>
                $updated
        );

    /*
    |--------------------------------------------------------------------------
    | Último build_xml vuelve a fallar
    |--------------------------------------------------------------------------
    */

    $buildDteXmlUseCase =
        Mockery::mock(
            BuildDteXmlUseCase::class
        );

    $buildDteXmlUseCase
        ->shouldReceive('execute')
        ->once()
        ->andThrow(
            $failure
        );

    /*
    |--------------------------------------------------------------------------
    | Handler real
    |--------------------------------------------------------------------------
    */

    $failureHandler =
        new HandleDocumentAutomationFailureService(
            documentRepository:
                $repository,

            scheduleRetryService:
                new ScheduleDocumentAutomationRetryService()
        );

    /*
    |--------------------------------------------------------------------------
    | Orquestador
    |--------------------------------------------------------------------------
    */

    $useCase =
        new AdvanceDteAutomationUseCase(
            documentRepository:
                $repository,

            automationPlannerService:
                new DteAutomationPlannerService(),

            prepareDteDocumentForXmlUseCase:
                $this->withoutConstructor(
                    PrepareDteDocumentForXmlUseCase::class
                ),

            buildDteXmlUseCase:
                $buildDteXmlUseCase,

            buildTedUseCase:
                $this->withoutConstructor(
                    BuildTedUseCase::class
                ),

            signDteXmlUseCase:
                $this->withoutConstructor(
                    SignDteXmlUseCase::class
                ),

            sendSignedDteToSiiUseCase:
                $this->withoutConstructor(
                    SendSignedDteToSiiUseCase::class
                ),

            sendSignedBoletaToSiiUseCase:
                $this->withoutConstructor(
                    SendSignedBoletaToSiiUseCase::class
                ),

            handleDocumentAutomationFailureService:
                $failureHandler
        );

        /*
        |--------------------------------------------------------------------------
        | Debe lanzar la excepción TERMINAL, no Scheduled
        |--------------------------------------------------------------------------
        */

        try {
            $useCase->execute(
                new AdvanceDteAutomationInputDto(
                    documentId:
                        1
                )
            );

            $this->fail(
                'Se esperaba DocumentAutomationRetryExhaustedException.'
            );

        } catch (
            DocumentAutomationRetryExhaustedException $exception
        ) {
            $this->assertSame(
                1,
                $exception->documentId()
            );

            $this->assertSame(
                'build_xml',
                $exception->action()
            );

            $this->assertSame(
                $failure,
                $exception->getPrevious()
            );

            $this->assertSame(
                'Falló definitivamente build_xml.',
                $exception->getMessage()
            );
        }
    }
    /*
    |--------------------------------------------------------------------------
    | Configuración común
    |--------------------------------------------------------------------------
    */

    private function configurarRetry(): void
    {
        config()->set(
            'dte.automation.document_retry.max_attempts',
            3
        );

        config()->set(
            'dte.automation.document_retry.backoff_seconds',
            [
                30,
                120,
                300,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Documento base
    |--------------------------------------------------------------------------
    */

    private function documento(
        array $items = [],
        ?DteType $dteType = null
    ): DteDocument {
        return new DteDocument(
            id:
                1,

            externalId:
                'advance-automation-retry-test',

            companyId:
                1,

            dteType:
                $dteType
                ?? DteType::BOLETA_ELECTRONICA,

            issueDate:
                '2026-09-27',

            status:
                DteStatus::FOLIO_ASSIGNED->value,

            receiver:
                new ReceiverData(
                    document:
                        '11111111-1',

                    name:
                        'Cliente prueba'
                ),

            netAmount:
                1000,

            exemptAmount:
                0,

            taxAmount:
                190,

            totalAmount:
                1190,

            items:
                $items,

            folio:
                77,

            siiEnvironment:
                'cert',

            externalSystemId:
                1,

            cafId:
                15,

            folioReservationId:
                21
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Crear dependencia sin ejecutar constructor
    |--------------------------------------------------------------------------
    |
    | Estas dependencias no llegan a utilizarse porque provocamos el fallo
    | antes de alcanzar esa parte del respectivo use case.
    |
    */
    public function test_fallo_de_prepare_for_xml_no_programa_document_retry_y_propaga_excepcion_original(): void
    {
        $this->configurarRetry();

        /*
        |--------------------------------------------------------------------------
        | Documento en READY_FOR_XML
        |--------------------------------------------------------------------------
        |
        | Para que PrepareDteDocumentForXmlUseCase alcance correctamente la
        | consulta de empresa necesitamos:
        |
        | - READY_FOR_XML
        | - sin folio
        | - al menos una línea de detalle
        |
        */

        $document =
            new DteDocument(
                id:
                    1,

                externalId:
                    'advance-automation-prepare-failure-test',

                companyId:
                    1,

                dteType:
                    DteType::BOLETA_ELECTRONICA,

                issueDate:
                    '2026-09-27',

                status:
                    DteStatus::READY_FOR_XML->value,

                receiver:
                    new ReceiverData(
                        document:
                            '11111111-1',

                        name:
                            'Cliente prueba'
                    ),

                netAmount:
                    1000,

                exemptAmount:
                    0,

                taxAmount:
                    190,

                totalAmount:
                    1190,

                items:
                    [
                        new DteLineItem(
                            lineNumber:
                                1,

                            itemCodeType:
                                null,

                            itemCode:
                                null,

                            name:
                                'Producto prueba',

                            description:
                                null,

                            quantity:
                                1,

                            unitPrice:
                                1000,

                            discountPercent:
                                0,

                            discountAmount:
                                0,

                            taxExempt:
                                false,

                            lineAmount:
                                1000
                        ),
                    ],

                folio:
                    null,

                siiEnvironment:
                    'cert',

                externalSystemId:
                    1
            );

        $failure =
            new RuntimeException(
                'Fallo técnico preparando documento.'
            );

        /*
        |--------------------------------------------------------------------------
        | Repositorio de documentos
        |--------------------------------------------------------------------------
        */

        $repository =
            Mockery::mock(
                DteDocumentRepositoryInterface::class
            );

        /*
        | Lectura inicial de AdvanceDteAutomationUseCase.
        */

        $repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(
                $document
            );

        /*
        | PrepareDteDocumentForXmlUseCase real bloquea el documento.
        |
        | Debe ocurrir UNA sola vez.
        |
        | Si HandleDocumentAutomationFailureService fuese llamado
        | incorrectamente, aparecería un segundo findByIdForUpdate() y
        | este test fallaría.
        */

        $repository
            ->shouldReceive('findByIdForUpdate')
            ->once()
            ->with(1)
            ->andReturn(
                $document
            );

        /*
        | Como prepare_for_xml NO pertenece al retry funcional interno,
        | no debe persistirse ningún estado de document_retry.
        */

        $repository
            ->shouldNotReceive(
                'update'
            );

        /*
        |--------------------------------------------------------------------------
        | Fallo técnico dentro de prepare_for_xml
        |--------------------------------------------------------------------------
        |
        | Dejamos que el use case real:
        |
        | - abra DB::transaction()
        | - bloquee el documento
        | - valide READY_FOR_XML
        |
        | y hacemos fallar la consulta de la empresa.
        |
        */

        $companyRepository =
            Mockery::mock(
                CompanyRepositoryInterface::class
            );

        $companyRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andThrow(
                $failure
            );

        /*
        |--------------------------------------------------------------------------
        | PrepareDteDocumentForXmlUseCase REAL
        |--------------------------------------------------------------------------
        */

        $prepareUseCase =
            new PrepareDteDocumentForXmlUseCase(
                documentRepository:
                    $repository,

                companyRepository:
                    $companyRepository,

                cafRepository:
                    Mockery::mock(
                        SiiCafRepositoryInterface::class
                    ),

                logRepository:
                    Mockery::mock(
                        IntegrationLogRepositoryInterface::class
                    ),

                preparationDomainService:
                    new DteDocumentPreparationDomainService()
            );

        /*
        |--------------------------------------------------------------------------
        | Handler real
        |--------------------------------------------------------------------------
        |
        | Lo inyectamos normalmente, pero este escenario NO debe llegar a
        | ejecutarlo.
        |
        */

        $failureHandler =
            new HandleDocumentAutomationFailureService(
                documentRepository:
                    $repository,

                scheduleRetryService:
                    new ScheduleDocumentAutomationRetryService()
            );

        /*
        |--------------------------------------------------------------------------
        | Orquestador
        |--------------------------------------------------------------------------
        */

        $useCase =
            new AdvanceDteAutomationUseCase(
                documentRepository:
                    $repository,

                automationPlannerService:
                    new DteAutomationPlannerService(),

                prepareDteDocumentForXmlUseCase:
                    $prepareUseCase,

                buildDteXmlUseCase:
                    $this->withoutConstructor(
                        BuildDteXmlUseCase::class
                    ),

                buildTedUseCase:
                    $this->withoutConstructor(
                        BuildTedUseCase::class
                    ),

                signDteXmlUseCase:
                    $this->withoutConstructor(
                        SignDteXmlUseCase::class
                    ),

                sendSignedDteToSiiUseCase:
                    $this->withoutConstructor(
                        SendSignedDteToSiiUseCase::class
                    ),

                sendSignedBoletaToSiiUseCase:
                    $this->withoutConstructor(
                        SendSignedBoletaToSiiUseCase::class
                    ),

                handleDocumentAutomationFailureService:
                    $failureHandler
            );

        /*
        |--------------------------------------------------------------------------
        | Ejecutar
        |--------------------------------------------------------------------------
        |
        | Debemos recibir exactamente la excepción original.
        |
        | NO:
        |
        | - DocumentAutomationRetryScheduledException
        | - DocumentAutomationRetryExhaustedException
        |
        */

        try {
            $useCase->execute(
                new AdvanceDteAutomationInputDto(
                    documentId:
                        1
                )
            );

            $this->fail(
                'Se esperaba que prepare_for_xml propagara la excepción original.'
            );

        } catch (RuntimeException $exception) {

            $this->assertSame(
                $failure,
                $exception
            );

            $this->assertSame(
                'Fallo técnico preparando documento.',
                $exception->getMessage()
            );
        }
    }
    public function test_fallo_de_send_boleta_no_programa_document_retry_y_propaga_excepcion_original(): void
    {
        $this->configurarRetry();

        /*
        |--------------------------------------------------------------------------
        | Documento firmado de familia boleta
        |--------------------------------------------------------------------------
        */

        $document =
            $this->documento()
                ->withSignedXml(
                    'xml/boleta_signed_test.xml'
                );

        $failure =
            new RuntimeException(
                'Fallo técnico enviando boleta.'
            );

        /*
        |--------------------------------------------------------------------------
        | Repositorio
        |--------------------------------------------------------------------------
        */

        $repository =
            Mockery::mock(
                DteDocumentRepositoryInterface::class
            );

        /*
        | Lectura inicial del orquestador.
        */

        $repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(
                $document
            );

        /*
        | Esta llamada corresponde exclusivamente a
        | SendSignedBoletaToSiiUseCase.
        |
        | Si HandleDocumentAutomationFailureService fuese ejecutado
        | incorrectamente existiría una segunda llamada.
        */

        $repository
            ->shouldReceive('findByIdForUpdate')
            ->once()
            ->with(1)
            ->andThrow(
                $failure
            );

        /*
        | document_retry NO debe persistir nada.
        */

        $repository
            ->shouldNotReceive(
                'update'
            );

        /*
        |--------------------------------------------------------------------------
        | SendSignedBoletaToSiiUseCase real, sin ejecutar constructor
        |--------------------------------------------------------------------------
        |
        | La clase es final.
        |
        | Sólo inicializamos documentRepository porque provocaremos el fallo
        | en la primera operación del execute().
        |
        */

        $sendBoletaUseCase =
            $this->withoutConstructorWithProperty(
                class:
                    SendSignedBoletaToSiiUseCase::class,

                property:
                    'documentRepository',

                value:
                    $repository
            );

        $failureHandler =
            new HandleDocumentAutomationFailureService(
                documentRepository:
                    $repository,

                scheduleRetryService:
                    new ScheduleDocumentAutomationRetryService()
            );

        $useCase =
            new AdvanceDteAutomationUseCase(
                documentRepository:
                    $repository,

                automationPlannerService:
                    new DteAutomationPlannerService(),

                prepareDteDocumentForXmlUseCase:
                    $this->withoutConstructor(
                        PrepareDteDocumentForXmlUseCase::class
                    ),

                buildDteXmlUseCase:
                    $this->withoutConstructor(
                        BuildDteXmlUseCase::class
                    ),

                buildTedUseCase:
                    $this->withoutConstructor(
                        BuildTedUseCase::class
                    ),

                signDteXmlUseCase:
                    $this->withoutConstructor(
                        SignDteXmlUseCase::class
                    ),

                sendSignedDteToSiiUseCase:
                    $this->withoutConstructor(
                        SendSignedDteToSiiUseCase::class
                    ),

                sendSignedBoletaToSiiUseCase:
                    $sendBoletaUseCase,

                handleDocumentAutomationFailureService:
                    $failureHandler
            );

        /*
        |--------------------------------------------------------------------------
        | Debe propagarse exactamente el fallo original
        |--------------------------------------------------------------------------
        */

        try {
            $useCase->execute(
                new AdvanceDteAutomationInputDto(
                    documentId:
                        1
                )
            );

            $this->fail(
                'Se esperaba que send_boleta propagara la excepción original.'
            );

        } catch (RuntimeException $exception) {

            $this->assertSame(
                $failure,
                $exception
            );

            $this->assertSame(
                'Fallo técnico enviando boleta.',
                $exception->getMessage()
            );
        }
    }

    public function test_fallo_de_send_factura_no_programa_document_retry_y_propaga_excepcion_original(): void
    {
        $this->configurarRetry();

        /*
        |--------------------------------------------------------------------------
        | Documento firmado de familia factura
        |--------------------------------------------------------------------------
        |
        | Usamos DteType::from(33) para evitar depender del nombre concreto
        | del case del enum.
        |
        */

        $document =
            $this->documento(
                dteType:
                    DteType::from(33)
            )
                ->withSignedXml(
                    'xml/factura_signed_test.xml'
                );

        $failure =
            new RuntimeException(
                'Fallo técnico enviando factura.'
            );

        /*
        |--------------------------------------------------------------------------
        | Repositorio
        |--------------------------------------------------------------------------
        */

        $repository =
            Mockery::mock(
                DteDocumentRepositoryInterface::class
            );

        $repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(
                $document
            );

        /*
        | Esta única llamada pertenece a SendSignedDteToSiiUseCase.
        |
        | No debe existir una segunda lectura con lock desde el handler de
        | document_retry.
        */

        $repository
            ->shouldReceive('findByIdForUpdate')
            ->once()
            ->with(1)
            ->andThrow(
                $failure
            );

        $repository
            ->shouldNotReceive(
                'update'
            );

        /*
        |--------------------------------------------------------------------------
        | SendSignedDteToSiiUseCase real
        |--------------------------------------------------------------------------
        */

        $sendFacturaUseCase =
            $this->withoutConstructorWithProperty(
                class:
                    SendSignedDteToSiiUseCase::class,

                property:
                    'documentRepository',

                value:
                    $repository
            );

        $failureHandler =
            new HandleDocumentAutomationFailureService(
                documentRepository:
                    $repository,

                scheduleRetryService:
                    new ScheduleDocumentAutomationRetryService()
            );

        $useCase =
            new AdvanceDteAutomationUseCase(
                documentRepository:
                    $repository,

                automationPlannerService:
                    new DteAutomationPlannerService(),

                prepareDteDocumentForXmlUseCase:
                    $this->withoutConstructor(
                        PrepareDteDocumentForXmlUseCase::class
                    ),

                buildDteXmlUseCase:
                    $this->withoutConstructor(
                        BuildDteXmlUseCase::class
                    ),

                buildTedUseCase:
                    $this->withoutConstructor(
                        BuildTedUseCase::class
                    ),

                signDteXmlUseCase:
                    $this->withoutConstructor(
                        SignDteXmlUseCase::class
                    ),

                sendSignedDteToSiiUseCase:
                    $sendFacturaUseCase,

                sendSignedBoletaToSiiUseCase:
                    $this->withoutConstructor(
                        SendSignedBoletaToSiiUseCase::class
                    ),

                handleDocumentAutomationFailureService:
                    $failureHandler
            );

        try {
            $useCase->execute(
                new AdvanceDteAutomationInputDto(
                    documentId:
                        1
                )
            );

            $this->fail(
                'Se esperaba que send_factura propagara la excepción original.'
            );

        } catch (RuntimeException $exception) {

            $this->assertSame(
                $failure,
                $exception
            );

            $this->assertSame(
                'Fallo técnico enviando factura.',
                $exception->getMessage()
            );
        }
    }
    public function test_error_deterministico_de_build_xml_lanza_excepcion_no_retryable(): void
    {
        $this->configurarRetry();

        /*
        |--------------------------------------------------------------------------
        | Documento listo para build_xml
        |--------------------------------------------------------------------------
        */

        $document =
            $this->documento();

        $failure =
            InvalidDteException::because(
                'El DTE contiene datos inválidos.'
            );

        /*
        |--------------------------------------------------------------------------
        | Repositorio
        |--------------------------------------------------------------------------
        */

        $repository =
            Mockery::mock(
                DteDocumentRepositoryInterface::class
            );

        /*
        | Lectura inicial realizada por AdvanceDteAutomationUseCase.
        */

        $repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(
                $document
            );

        /*
        | Después del fallo, HandleDocumentAutomationFailureService
        | recarga el documento con lock.
        */

        $repository
            ->shouldReceive('findByIdForUpdate')
            ->once()
            ->with(1)
            ->andReturn(
                $document
            );

        /*
        |--------------------------------------------------------------------------
        | Estado terminal NO RETRYABLE
        |--------------------------------------------------------------------------
        */

        $repository
            ->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(
                    function (
                        DteDocument $updated
                    ): bool {
                        return
                            $updated->status()
                                === DteStatus::FOLIO_ASSIGNED->value

                            && $updated->automationRetryAction()
                                === 'build_xml'

                            && $updated->automationRetryCount()
                                === 0

                            && $updated->automationNextRetryAt()
                                === null

                            && $updated->lastErrorCode()
                                === 'AUTOMATION_BUILD_XML_NOT_RETRYABLE'

                            && $updated->lastErrorMessage()
                                === 'El DTE contiene datos inválidos.'

                            && $updated->folio()
                                === 77

                            && $updated->cafId()
                                === 15

                            && $updated->folioReservationId()
                                === 21

                            && $updated->externalSystemId()
                                === 1;
                    }
                )
            )
            ->andReturnUsing(
                fn (
                    DteDocument $updated
                ): DteDocument =>
                    $updated
            );

        /*
        |--------------------------------------------------------------------------
        | build_xml falla con una DomainException
        |--------------------------------------------------------------------------
        */

        $buildDteXmlUseCase =
            Mockery::mock(
                BuildDteXmlUseCase::class
            );

        $buildDteXmlUseCase
            ->shouldReceive('execute')
            ->once()
            ->andThrow(
                $failure
            );

        /*
        |--------------------------------------------------------------------------
        | Handler real
        |--------------------------------------------------------------------------
        */

        $failureHandler =
            new HandleDocumentAutomationFailureService(
                documentRepository:
                    $repository,

                scheduleRetryService:
                    new ScheduleDocumentAutomationRetryService()
            );

        /*
        |--------------------------------------------------------------------------
        | Orquestador
        |--------------------------------------------------------------------------
        */

        $useCase =
            new AdvanceDteAutomationUseCase(
                documentRepository:
                    $repository,

                automationPlannerService:
                    new DteAutomationPlannerService(),

                prepareDteDocumentForXmlUseCase:
                    $this->withoutConstructor(
                        PrepareDteDocumentForXmlUseCase::class
                    ),

                buildDteXmlUseCase:
                    $buildDteXmlUseCase,

                buildTedUseCase:
                    $this->withoutConstructor(
                        BuildTedUseCase::class
                    ),

                signDteXmlUseCase:
                    $this->withoutConstructor(
                        SignDteXmlUseCase::class
                    ),

                sendSignedDteToSiiUseCase:
                    $this->withoutConstructor(
                        SendSignedDteToSiiUseCase::class
                    ),

                sendSignedBoletaToSiiUseCase:
                    $this->withoutConstructor(
                        SendSignedBoletaToSiiUseCase::class
                    ),

                handleDocumentAutomationFailureService:
                    $failureHandler
            );

        /*
        |--------------------------------------------------------------------------
        | Debe distinguir NOT_RETRYABLE de EXHAUSTED
        |--------------------------------------------------------------------------
        */

        try {
            $useCase->execute(
                new AdvanceDteAutomationInputDto(
                    documentId:
                        1
                )
            );

            $this->fail(
                'Se esperaba DocumentAutomationNotRetryableException.'
            );

        } catch (
            DocumentAutomationNotRetryableException $exception
        ) {
            $this->assertSame(
                1,
                $exception->documentId()
            );

            $this->assertSame(
                'build_xml',
                $exception->action()
            );

            $this->assertSame(
                $failure,
                $exception->getPrevious()
            );

            $this->assertSame(
                'El DTE contiene datos inválidos.',
                $exception->getMessage()
            );
        }
    }
    private function withoutConstructor(
        string $class
    ): object {
        return (
            new ReflectionClass(
                $class
            )
        )->newInstanceWithoutConstructor();
    }
    private function withoutConstructorWithProperty(
        string $class,
        string $property,
        mixed $value
    ): object {
        $reflection =
            new ReflectionClass(
                $class
            );

        $instance =
            $reflection
                ->newInstanceWithoutConstructor();

        $reflection
            ->getProperty(
                $property
            )
            ->setValue(
                $instance,
                $value
            );

        return $instance;
    }
}
