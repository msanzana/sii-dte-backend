<?php
namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Application\DTOs\PasswordLoginInputDto;
use App\Modules\Auth\Application\DTOs\PasswordLoginResultDto;
use App\Modules\Auth\Domain\Exception\InvalidCredentialsException;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\UserCompanyAccessRepositoryInterface;
use App\Modules\Auth\Infrastructure\Security\JwtHs256Service;
use Illuminate\Support\Facades\Hash;

final class PasswordLoginUseCase
{
    public function __construct(
        private readonly AuthUserRepositoryInterface $authUserRepository,
        private readonly UserCompanyAccessRepositoryInterface $userCompanyAccessRepository,
        private readonly JwtHs256Service $jwtHs256Service,
    )
    {}

    public function execute(
        PasswordLoginInputDto $input
    ): PasswordLoginResultDto
    {
        $user = $this->authUserRepository->findActiveByEmail($input->email);

        if(!$user)
        {
            throw InvalidCredentialsException::because();
        }

        if(!Hash::check($input->password, $user->passwordHash()))
        {
            throw InvalidCredentialsException::because();
        }

        $companies = $this->userCompanyAccessRepository->findAccessibleCompaniesByUserId($user->id());

        $token = $this->jwtHs256Service->issue([
            'sub' => (string) $user->id(),
            'uid' => $user->id(),
            'email' => $user->email(),
            'stage' => 'pre_company',
        ],(int) config('platform_auth.jwt.ttl_seconds.pre_company',600));

        $this->authUserRepository->touchLastLoginAt($user->id());

        return new PasswordLoginResultDto(
            userId: $user->id(),
            fullName: $user->fullName(),
            email: $user->email(),
            token: $token,
            tokenStage: 'pre_company',
            expiresInSeconds: (int) config('platform_auth.jwt.ttl_seconds.pre_company'),
            companiesCount: count($companies),
        );
    }
}
