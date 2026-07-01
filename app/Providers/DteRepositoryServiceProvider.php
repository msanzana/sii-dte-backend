<?php

namespace App\Providers;

use App\Modules\Dte\Domain\RepositoryContracts\CityRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyCertificateNoticeRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\LocationRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiCafRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiCertificateRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SystemSettingRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentCityRepository;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentCompanyCertificateNoticeRepository;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentCompanyRepository;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentDteDocumentRepository;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentIntegrationLogRepository;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentLocationRepository;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentSiiCafRepository;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentSiiCertificateRepository;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentSiiDispatchRepository;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentSystemSettingRepository;
use Illuminate\Support\ServiceProvider;

class DteRepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CompanyRepositoryInterface::class,
            EloquentCompanyRepository::class
        );

        $this->app->bind(
            CityRepositoryInterface::class,
            EloquentCityRepository::class
        );

        $this->app->bind(
            LocationRepositoryInterface::class,
            EloquentLocationRepository::class
        );

        $this->app->bind(
            DteDocumentRepositoryInterface::class,
            EloquentDteDocumentRepository::class
        );

        $this->app->bind(
            SiiCertificateRepositoryInterface::class,
            EloquentSiiCertificateRepository::class
        );

        $this->app->bind(
            SiiCafRepositoryInterface::class,
            EloquentSiiCafRepository::class
        );

        $this->app->bind(
            IntegrationLogRepositoryInterface::class,
            EloquentIntegrationLogRepository::class
        );

        $this->app->bind(
            SystemSettingRepositoryInterface::class,
            EloquentSystemSettingRepository::class
        );
        $this->app->bind(
            SiiDispatchRepositoryInterface::class,
            EloquentSiiDispatchRepository::class
        );

        $this->app->bind(
            CompanyCertificateNoticeRepositoryInterface::class,
            EloquentCompanyCertificateNoticeRepository::class
        );
    }

    public function boot(): void
    {
    }
}
