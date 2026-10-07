<?php

namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\Exceptions\DteTypeNotAuthorizedException;
use App\Modules\Dte\Domain\RepositoryContracts\DteTypeAuthorizationRepositoryInterface;

final class ValidateDteTypeAuthorizationService
{
    public function __construct(
        private readonly DteTypeAuthorizationRepositoryInterface $repository
    ) {
    }

    public function execute(
        int $companyId,
        ?int $externalSystemId,
        int $dteType
    ): void {
        /*
         * 1. El tipo debe existir en el catálogo y estar activo.
         */
        if (!$this->repository->existsActiveTypeByCode($dteType)) {
            throw DteTypeNotAuthorizedException::inactiveOrUnknown(
                $dteType
            );
        }

        /*
         * 2. El catálogo puede contener tipos futuros.
         *
         * Que un código exista en dte_document_types no significa
         * automáticamente que el backend ya sepa construir XML,
         * TED, firmarlo y enviarlo al SII.
         *
         * Usamos tryFrom() y NO from() para evitar ValueError.
         */
        if (DteType::tryFrom($dteType) === null) {
            throw DteTypeNotAuthorizedException::unsupportedByBackend(
                $dteType
            );
        }

        /*
         * 3. Debe existir autorización a nivel empresa.
         */
        if (
            !$this->repository->isAuthorizedForCompany(
                $companyId,
                $dteType
            )
        ) {
            throw DteTypeNotAuthorizedException::forCompany(
                $companyId,
                $dteType
            );
        }

        /*
         * 4. Cuando el documento pertenece a un sistema externo,
         * también se exige autorización específica para ese sistema.
         */
        if (
            $externalSystemId !== null
            && !$this->repository->isAuthorizedForSystem(
                $companyId,
                $externalSystemId,
                $dteType
            )
        ) {
            throw DteTypeNotAuthorizedException::forSystem(
                $companyId,
                $externalSystemId,
                $dteType
            );
        }
    }
}