<?php

namespace App\Providers;

use App\Modules\Auth\Domain\RepositoryContracts\AuthCompanyStateRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthPasswordResetTokenRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthRefreshTokenRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthRolesRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\UserCompanyAccessRepositoryInterface;
use App\Modules\Auth\Infrastructure\Persistence\Repositories\EloquentAuthCompanyStateRepository;
use App\Modules\Auth\Infrastructure\Persistence\Repositories\EloquentAuthPasswordResetTokenRepository;
use App\Modules\Auth\Infrastructure\Persistence\Repositories\EloquentAuthRefreshTokenRepository;
use App\Modules\Auth\Infrastructure\Persistence\Repositories\EloquentAuthRolesRepository;
use App\Modules\Auth\Infrastructure\Persistence\Repositories\EloquentAuthUserRepository;
use App\Modules\Auth\Infrastructure\Persistence\Repositories\EloquentUserCompanyAccessRepository;
use Illuminate\Support\ServiceProvider;

class AuthRepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AuthUserRepositoryInterface::class,
            EloquentAuthUserRepository::class
        );

        $this->app->bind(
            UserCompanyAccessRepositoryInterface::class,
            EloquentUserCompanyAccessRepository::class
        );

        $this->app->bind(
            AuthCompanyStateRepositoryInterface::class,
            EloquentAuthCompanyStateRepository::class
        );

        $this->app->bind(
            AuthRefreshTokenRepositoryInterface::class,
            EloquentAuthRefreshTokenRepository::class
        );

        $this->app->bind(
            AuthPasswordResetTokenRepositoryInterface::class,
            EloquentAuthPasswordResetTokenRepository::class
        );

        $this->app->bind(
            AuthRolesRepositoryInterface::class,
            EloquentAuthRolesRepository::class
        );
    }
}
