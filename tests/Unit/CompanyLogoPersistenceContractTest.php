<?php

namespace Tests\Unit;

use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CompanyEloquentModel;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class CompanyLogoPersistenceContractTest extends TestCase
{
    public function test_modelo_eloquent_permite_metadata_del_logo(): void
    {
        $model =
            new CompanyEloquentModel();

        $fillable =
            $model->getFillable();

        $this->assertContains(
            'logo_path',
            $fillable
        );

        $this->assertContains(
            'logo_mime_type',
            $fillable
        );

        $this->assertContains(
            'logo_sha256',
            $fillable
        );
    }

    public function test_repositorio_create_y_update_persisten_metadata_del_logo(): void
    {
        $reflection =
            new ReflectionClass(
                \App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentCompanyRepository::class
            );

        $source =
            file_get_contents(
                $reflection->getFileName()
            );

        $this->assertIsString(
            $source
        );

        $this->assertSame(
            2,
            substr_count(
                $source,
                "'logo_path' => \$company->logoPath()"
            )
        );

        $this->assertSame(
            2,
            substr_count(
                $source,
                "'logo_mime_type' => \$company->logoMimeType()"
            )
        );

        $this->assertSame(
            2,
            substr_count(
                $source,
                "'logo_sha256' => \$company->logoSha256()"
            )
        );
    }

    public function test_mapper_reconstruye_metadata_del_logo(): void
    {
        $reflection =
            new ReflectionClass(
                \App\Modules\Dte\Infrastructure\Persistence\Mappers\CompanyPersistenceMapper::class
            );

        $source =
            file_get_contents(
                $reflection->getFileName()
            );

        $this->assertIsString(
            $source
        );

        $this->assertStringContainsString(
            'logoPath: $model->logo_path',
            $source
        );

        $this->assertStringContainsString(
            'logoMimeType: $model->logo_mime_type',
            $source
        );

        $this->assertStringContainsString(
            'logoSha256: $model->logo_sha256',
            $source
        );
    }
}