<?php

namespace App\Modules\Auth\Application\Services;

use App\Modules\Auth\Application\DTOs\CreatedTechnicalUserDto;
use App\Modules\Auth\Domain\Exception\AuthAdministrationException;
use App\Modules\Auth\Domain\RepositoryContracts\AuthCompanyStateRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\TechnicalUserSystemAccessRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\UserCompanyAccessRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\ExternalSystemRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class CreateTechnicalUserService
{
    public function __construct(
        private readonly AuthCompanyStateRepositoryInterface $companies,
        private readonly ExternalSystemRepositoryInterface $systems,
        private readonly AuthUserRepositoryInterface $users,
        private readonly UserCompanyAccessRepositoryInterface $companyAccesses,
        private readonly TechnicalUserSystemAccessRepositoryInterface $systemAccesses,
    ) {
    }

    public function execute(int $companyId, int $externalSystemId): CreatedTechnicalUserDto
    {
        return DB::transaction(function () use ($companyId, $externalSystemId) {
            $company = $this->companies->findById($companyId);
            if ($company === null || !$company->isActive()) {
                throw AuthAdministrationException::because(
                    'La empresa no existe o esta inactiva.'
                );
            }

            // La busqueda filtrada por empresa impide concesiones entre empresas.
            $system = $this->systems->findByCompanyAndId($companyId, $externalSystemId);
            if (
                $system === null
                || !$system->isActive()
                || $system->companyId() !== $companyId
            ) {
                throw AuthAdministrationException::because(
                    'El sistema externo no existe, esta inactivo o no pertenece a la empresa.'
                );
            }

            $uuid = (string) Str::uuid();
            // 32 bytes aleatorios = 256 bits; representacion imprimible de 64 caracteres.
            $plainPassword = bin2hex(random_bytes(32));

            $user = $this->users->createTechnical(
                loginUuid: $uuid,
                passwordHash: Hash::make($plainPassword),
            );

            // Cuenta confinada a una sola empresa: sin selector ni roles de administrador.
            $this->companyAccesses->assignCompanyToUser(
                userId: $user->id(),
                companyId: $companyId,
                isDefault: true,
                canSelectCompany: false,
                roleIds: [],
            );

            // Su FK compuesta exige que existan tanto el acceso de empresa
            // como el sistema perteneciente a esa misma empresa.
            $this->systemAccesses->grant(
                userId: $user->id(),
                companyId: $companyId,
                externalSystemId: $externalSystemId,
            );

            // El password plano no se guarda en ninguna tabla; mostrar una sola vez.
            return new CreatedTechnicalUserDto(
                userId: $user->id(),
                companyId: $companyId,
                externalSystemId: $externalSystemId,
                loginUuid: $uuid,
                plainPassword: $plainPassword,
            );
        }, attempts: 1);
    }
}
