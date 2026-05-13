<?php
namespace App\Providers;

use App\Modules\Dte\Domain\RepositoryContracts\CityRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SystemSettingRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentCityRepository;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentCompanyRepository;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentDteDocumentRepository;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentIntegrationLogRepository;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentSystemSettingRepository;
use Carbon\Laravel\ServiceProvider;

class DteRepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CompanyRepositoryInterface::class,
            EloquentCompanyRepository::class,
        );
        $this->app->bind(
            CityRepositoryInterface::class,
            EloquentCityRepository::class,
        );
        $this->app->bind(
            DteDocumentRepositoryInterface::class,
            EloquentDteDocumentRepository::class,
        );
        $this->app->bind(
            IntegrationLogRepositoryInterface::class,
            EloquentIntegrationLogRepository::class,
        );
        $this->app->bind(
            SystemSettingRepositoryInterface::class,
            EloquentSystemSettingRepository::class,
        );
    }
    public function boot(): void
    {}
}
