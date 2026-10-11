<?php

namespace App\Modules\Dte\Application\UseCases\Company;

use App\Modules\Dte\Domain\Entities\Company;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\StorageContracts\CompanyLogoStorageInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

final class UploadCompanyLogoUseCase
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly CompanyLogoStorageInterface $logoStorage,
    ) {}

    public function execute(
        int $companyId,
        string $contents,
        string $declaredMimeType
    ): Company {
        if ($companyId <= 0) {
            throw new \InvalidArgumentException(
                'El identificador de empresa debe ser positivo.'
            );
        }

        $company =
            $this->companyRepository
                ->findById($companyId);

        if ($company === null) {
            throw CompanyNotFoundException::withId(
                $companyId
            );
        }

        $previousLogoPath =
            $company->logoPath();

        /*
         * Primero se guarda físicamente el nuevo archivo.
         *
         * Nunca eliminamos el logo anterior antes de que:
         * 1. el nuevo archivo sea válido,
         * 2. quede almacenado,
         * 3. la BD apunte correctamente al nuevo logo.
         */
        $newLogo =
            $this->logoStorage
                ->store(
                    companyId:
                        $companyId,
                    contents:
                        $contents,
                    declaredMimeType:
                        $declaredMimeType,
                );
        $samePhysicalPath =
            $previousLogoPath !== null
            && trim($previousLogoPath) !== ''
            && $previousLogoPath === $newLogo->path();
        try {
            $updatedCompany =
                DB::transaction(
                    fn (): Company =>
                        $this->companyRepository
                            ->update(
                                $company->withLogo(
                                    $newLogo
                                )
                            )
                );
        } catch (Throwable $exception) {
            /*
            * Si el nuevo archivo tiene la misma ruta física que el logo
            * actualmente vigente, no debemos eliminarlo: la BD todavía
            * puede seguir apuntando a esa misma ruta.
            */
            if (!$samePhysicalPath) {
                try {
                    $this->logoStorage
                        ->delete(
                            $newLogo->path()
                        );
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            throw $exception;
        }

        /*
         * Sólo después del commit eliminamos el archivo anterior.
         *
         * Si falla esta eliminación, la empresa sigue apuntando
         * al nuevo logo válido. Como máximo queda un archivo
         * antiguo huérfano que puede limpiarse posteriormente.
         */
        if (
            $previousLogoPath !== null
            && trim($previousLogoPath) !== ''
            && $previousLogoPath !== $newLogo->path()
        ) {
            try {
                $this->logoStorage
                    ->delete(
                        $previousLogoPath
                    );
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }
        }

        return $updatedCompany;
    }
}