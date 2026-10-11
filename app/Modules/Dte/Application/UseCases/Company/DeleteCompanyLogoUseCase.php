<?php

namespace App\Modules\Dte\Application\UseCases\Company;

use App\Modules\Dte\Domain\Entities\Company;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\StorageContracts\CompanyLogoStorageInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DeleteCompanyLogoUseCase
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly CompanyLogoStorageInterface $logoStorage,
    ) {}

    public function execute(
        int $companyId
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

        /*
         * DELETE idempotente:
         * si no existe logo, no modificamos nada.
         */
        if (!$company->hasLogo()) {
            return $company;
        }

        $previousLogoPath =
            $company->logoPath();

        $updatedCompany =
            DB::transaction(
                fn (): Company =>
                    $this->companyRepository
                        ->update(
                            $company->withoutLogo()
                        )
            );

        /*
         * Primero la BD deja de referenciarlo.
         * Después se elimina físicamente.
         */
        if (
            $previousLogoPath !== null
            && trim($previousLogoPath) !== ''
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