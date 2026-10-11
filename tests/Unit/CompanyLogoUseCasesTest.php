<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\UseCases\Company\DeleteCompanyLogoUseCase;
use App\Modules\Dte\Application\UseCases\Company\UploadCompanyLogoUseCase;
use App\Modules\Dte\Domain\Entities\Company;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\StorageContracts\CompanyLogoStorageInterface;
use App\Modules\Dte\Domain\ValueObjects\CompanyLogoFile;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class CompanyLogoUseCasesTest extends TestCase
{
    private function company(): Company
    {
        return new Company(
            id: 1,
            rut: '76123456-7',
            rutBody: '76123456',
            rutDv: '7',
            legalName: 'Empresa Prueba SpA',
            tradeName: 'Empresa Prueba',
            giro: 'Servicios',
            siiActivityCode: '620200',
            address: 'Dirección 123',
            cityId: 1,
            dteEmail: 'dte@example.cl',
            resolutionNumber: '80',
            resolutionDate: '2026-01-01',
            siiEnvironment: 'cert',
            isActive: true,
            siiOfficeName: 'TEMUCO',
        );
    }

    private function logo(
        string $name
    ): CompanyLogoFile {
        return new CompanyLogoFile(
            path:
                'dte/companies/1/branding/'
                . $name
                . '.png',
            mimeType:
                'image/png',
            sha256:
                str_repeat(
                    $name === 'old' ? 'a' : 'b',
                    64
                ),
        );
    }

    public function test_carga_logo_nuevo_y_actualiza_empresa(): void
    {
        $repository =
            Mockery::mock(
                CompanyRepositoryInterface::class
            );

        $storage =
            Mockery::mock(
                CompanyLogoStorageInterface::class
            );

        $company =
            $this->company();

        $newLogo =
            $this->logo('new');

        $repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($company);

        $storage
            ->shouldReceive('store')
            ->once()
            ->andReturn($newLogo);

        $repository
            ->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(
                    fn (Company $updated): bool =>
                        $updated->hasLogo()
                        && $updated->logoPath()
                            === $newLogo->path()
                )
            )
            ->andReturnUsing(
                fn (Company $updated): Company =>
                    $updated
            );

        $storage
            ->shouldNotReceive('delete');

        $result =
            (new UploadCompanyLogoUseCase(
                $repository,
                $storage
            ))->execute(
                companyId: 1,
                contents: 'binary-image',
                declaredMimeType: 'image/png',
            );

        $this->assertTrue(
            $result->hasLogo()
        );

        $this->assertSame(
            $newLogo->path(),
            $result->logoPath()
        );
    }

    public function test_reemplazo_elimina_logo_anterior_despues_de_actualizar(): void
    {
        $repository =
            Mockery::mock(
                CompanyRepositoryInterface::class
            );

        $storage =
            Mockery::mock(
                CompanyLogoStorageInterface::class
            );

        $oldLogo =
            $this->logo('old');

        $newLogo =
            $this->logo('new');

        $company =
            $this->company()
                ->withLogo(
                    $oldLogo
                );

        $repository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($company);

        $storage
            ->shouldReceive('store')
            ->once()
            ->andReturn($newLogo);

        $repository
            ->shouldReceive('update')
            ->once()
            ->andReturnUsing(
                fn (Company $updated): Company =>
                    $updated
            );

        $storage
            ->shouldReceive('delete')
            ->once()
            ->with(
                $oldLogo->path()
            );

        $result =
            (new UploadCompanyLogoUseCase(
                $repository,
                $storage
            ))->execute(
                1,
                'binary-image',
                'image/png'
            );

        $this->assertSame(
            $newLogo->path(),
            $result->logoPath()
        );
    }

    public function test_si_falla_persistencia_elimina_logo_nuevo(): void
    {
        $repository =
            Mockery::mock(
                CompanyRepositoryInterface::class
            );

        $storage =
            Mockery::mock(
                CompanyLogoStorageInterface::class
            );

        $newLogo =
            $this->logo('new');

        $repository
            ->shouldReceive('findById')
            ->once()
            ->andReturn(
                $this->company()
            );

        $storage
            ->shouldReceive('store')
            ->once()
            ->andReturn(
                $newLogo
            );

        $repository
            ->shouldReceive('update')
            ->once()
            ->andThrow(
                new RuntimeException(
                    'Fallo persistencia'
                )
            );

        $storage
            ->shouldReceive('delete')
            ->once()
            ->with(
                $newLogo->path()
            );

        $this->expectException(
            RuntimeException::class
        );

        (new UploadCompanyLogoUseCase(
            $repository,
            $storage
        ))->execute(
            1,
            'binary-image',
            'image/png'
        );
    }

    public function test_elimina_logo_y_limpia_metadata(): void
    {
        $repository =
            Mockery::mock(
                CompanyRepositoryInterface::class
            );

        $storage =
            Mockery::mock(
                CompanyLogoStorageInterface::class
            );

        $oldLogo =
            $this->logo('old');

        $company =
            $this->company()
                ->withLogo(
                    $oldLogo
                );

        $repository
            ->shouldReceive('findById')
            ->once()
            ->andReturn(
                $company
            );

        $repository
            ->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(
                    fn (Company $updated): bool =>
                        !$updated->hasLogo()
                )
            )
            ->andReturnUsing(
                fn (Company $updated): Company =>
                    $updated
            );

        $storage
            ->shouldReceive('delete')
            ->once()
            ->with(
                $oldLogo->path()
            );

        $result =
            (new DeleteCompanyLogoUseCase(
                $repository,
                $storage
            ))->execute(1);

        $this->assertFalse(
            $result->hasLogo()
        );

        $this->assertNull(
            $result->logoPath()
        );
    }

    public function test_eliminar_sin_logo_es_idempotente(): void
    {
        $repository =
            Mockery::mock(
                CompanyRepositoryInterface::class
            );

        $storage =
            Mockery::mock(
                CompanyLogoStorageInterface::class
            );

        $company =
            $this->company();

        $repository
            ->shouldReceive('findById')
            ->once()
            ->andReturn(
                $company
            );

        $repository
            ->shouldNotReceive('update');

        $storage
            ->shouldNotReceive('delete');

        $result =
            (new DeleteCompanyLogoUseCase(
                $repository,
                $storage
            ))->execute(1);

        $this->assertFalse(
            $result->hasLogo()
        );
    }
    public function test_si_falla_persistencia_con_la_misma_ruta_no_elimina_logo_vigente(): void
    {
        $repository =
            Mockery::mock(
                CompanyRepositoryInterface::class
            );

        $storage =
            Mockery::mock(
                CompanyLogoStorageInterface::class
            );

        $currentLogo =
            $this->logo('old');

        $company =
            $this->company()
                ->withLogo(
                    $currentLogo
                );

        $repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(
                $company
            );

        /*
        * Simulamos volver a subir exactamente el mismo archivo.
        * Como el nombre físico depende del SHA-256, el storage
        * devuelve exactamente la misma ruta que ya está vigente.
        */
        $storage
            ->shouldReceive('store')
            ->once()
            ->andReturn(
                $currentLogo
            );

        $repository
            ->shouldReceive('update')
            ->once()
            ->andThrow(
                new RuntimeException(
                    'Fallo persistencia'
                )
            );

        /*
        * La ruta existente sigue siendo la que la BD considera válida.
        * Si el rollback la eliminara, dejaríamos metadata apuntando
        * a un archivo inexistente.
        */
        $storage
            ->shouldNotReceive('delete');

        $this->expectException(
            RuntimeException::class
        );

        (new UploadCompanyLogoUseCase(
            $repository,
            $storage
        ))->execute(
            1,
            'binary-image',
            'image/png'
        );
    }
}