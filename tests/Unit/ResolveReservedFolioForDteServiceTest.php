<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\DTOs\CreateDteDocumentInputDto;
use App\Modules\Dte\Application\DTOs\ReceiverInputDto;
use App\Modules\Dte\Application\Services\ResolveReservedFolioForDteService;
use App\Modules\Dte\Application\Services\ValidateExternalSystemAccessService;
use App\Modules\Dte\Domain\Entities\ExternalSystem;
use App\Modules\Dte\Domain\Exceptions\DteReservedFolioException;
use App\Modules\Dte\Domain\RepositoryContracts\ExternalSystemRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailRepositoryInterface;
use Mockery;
use Tests\TestCase;

final class ResolveReservedFolioForDteServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_rechaza_tipo_propuesto_distinto_del_tipo_del_documento(): void
    {
        $externalSystemRepository = Mockery::mock(
            ExternalSystemRepositoryInterface::class
        );

        $externalSystemRepository
            ->shouldReceive('findByCompanyAndId')
            ->once()
            ->with(1, 1)
            ->andReturn(
                new ExternalSystem(
                    id: 1,
                    companyId: 1,
                    code: '1',
                    name: 'Sistema prueba',
                    description: null,
                    isActive: true,
                )
            );

        $folioDetailRepository = Mockery::mock(
            FolioDetailRepositoryInterface::class
        );

        /*
         * La incoherencia debe detectarse ANTES
         * de consultar un folio.
         */
        $folioDetailRepository
            ->shouldNotReceive(
                'findReservedForDocumentCreationForUpdate'
            );

        $service = new ResolveReservedFolioForDteService(
            new ValidateExternalSystemAccessService(
                $externalSystemRepository
            ),
            $folioDetailRepository
        );

        $input = $this->input(
            dteType: 33,
            proposedSiiDocumentType: 39,
        );

        $this->expectException(
            DteReservedFolioException::class
        );

        $this->expectExceptionMessage(
            'El tipo DTE 39 indicado para el folio no coincide con el tipo 33 del documento.'
        );

        $service->execute($input);
    }

    public function test_tipo_propuesto_coincidente_no_reemplaza_dte_type_en_busqueda_del_folio(): void
    {
        $externalSystemRepository = Mockery::mock(
            ExternalSystemRepositoryInterface::class
        );

        $externalSystemRepository
            ->shouldReceive('findByCompanyAndId')
            ->once()
            ->with(1, 1)
            ->andReturn(
                new ExternalSystem(
                    id: 1,
                    companyId: 1,
                    code: '1',
                    name: 'Sistema prueba',
                    description: null,
                    isActive: true,
                )
            );

        $folioDetailRepository = Mockery::mock(
            FolioDetailRepositoryInterface::class
        );

        /*
         * La consulta debe usar dteType = 33.
         *
         * proposedSiiDocumentType solamente confirma
         * coherencia; no selecciona el tipo de folio.
         */
        $folioDetailRepository
            ->shouldReceive(
                'findReservedForDocumentCreationForUpdate'
            )
            ->once()
            ->with(
                1,
                1,
                '33',
                15,
                null,
                null,
                null
            )
            ->andReturn(null);

        $service = new ResolveReservedFolioForDteService(
            new ValidateExternalSystemAccessService(
                $externalSystemRepository
            ),
            $folioDetailRepository
        );

        $input = $this->input(
            dteType: 33,
            proposedSiiDocumentType: 33,
        );

        /*
         * Devolvemos null desde el repositorio deliberadamente.
         * Si llegó a buscar el folio correctamente, debe terminar
         * en la excepción "folio no disponible".
         */
        $this->expectException(
            DteReservedFolioException::class
        );

        $this->expectExceptionMessage(
            'El folio 15 no existe, o no está reservado, ya fue utilizado o no pertenece a la distribución solicitada'
        );

        $service->execute($input);
    }

    private function input(
        int $dteType,
        ?int $proposedSiiDocumentType
    ): CreateDteDocumentInputDto {
        return new CreateDteDocumentInputDto(
            companyId: 1,
            dteType: $dteType,
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

            externalId: null,
            headerPayload: null,
            rawInput: null,

            externalSystemId: 1,
            proposedFolio: 15,

            proposedSiiDocumentType:
                $proposedSiiDocumentType,

            branchOfficeNumber: null,
            facilityNumber: null,
            externalBranchCode: null,
        );
    }
}