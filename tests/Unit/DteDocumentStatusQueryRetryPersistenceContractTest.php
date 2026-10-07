<?php

namespace Tests\Unit;

use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\DteDocumentEloquentModel;
use Tests\TestCase;

final class DteDocumentStatusQueryRetryPersistenceContractTest
    extends TestCase
{
    public function test_modelo_eloquent_permite_campos_de_retry_de_consulta(): void
    {
        $model = new DteDocumentEloquentModel();

        $this->assertTrue(
            $model->isFillable(
                'status_query_retry_count'
            ),
            'El modelo debe permitir status_query_retry_count.'
        );

        $this->assertTrue(
            $model->isFillable(
                'status_query_next_at'
            ),
            'El modelo debe permitir status_query_next_at.'
        );
    }

    public function test_repositorio_create_y_update_persisten_retry_de_consulta(): void
    {
        $source = file_get_contents(
            app_path(
                'Modules/Dte/Infrastructure/Persistence/Repositories/EloquentDteDocumentRepository.php'
            )
        );

        $this->assertIsString(
            $source
        );

        preg_match_all(
            '/[\'"]status_query_retry_count[\'"]\s*=>\s*\$document->statusQueryRetryCount\(\)/s',
            $source,
            $countMatches
        );

        preg_match_all(
            '/[\'"]status_query_next_at[\'"]\s*=>\s*\$document->statusQueryNextAt\(\)/s',
            $source,
            $nextAtMatches
        );

        $this->assertGreaterThanOrEqual(
            2,
            count($countMatches[0]),
            'create() y update() deben persistir status_query_retry_count.'
        );

        $this->assertGreaterThanOrEqual(
            2,
            count($nextAtMatches[0]),
            'create() y update() deben persistir status_query_next_at.'
        );
    }

    public function test_mapper_reconstruye_retry_de_consulta_desde_eloquent(): void
    {
        $source = file_get_contents(
            app_path(
                'Modules/Dte/Infrastructure/Persistence/Mappers/DteDocumentPersistenceMapper.php'
            )
        );

        $this->assertIsString(
            $source
        );

        $this->assertStringContainsString(
            'statusQueryRetryCount:',
            $source,
            'El mapper debe reconstruir statusQueryRetryCount.'
        );

        $this->assertStringContainsString(
            'statusQueryNextAt:',
            $source,
            'El mapper debe reconstruir statusQueryNextAt.'
        );

        $this->assertStringContainsString(
            'status_query_retry_count',
            $source
        );

        $this->assertStringContainsString(
            'status_query_next_at',
            $source
        );
    }
}