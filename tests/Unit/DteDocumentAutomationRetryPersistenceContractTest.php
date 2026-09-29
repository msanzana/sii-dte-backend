<?php

namespace Tests\Unit;

use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\DteDocumentEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\DteDocumentPersistenceMapper;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentDteDocumentRepository;
use ReflectionClass;
use Tests\TestCase;

final class DteDocumentAutomationRetryPersistenceContractTest extends TestCase
{
    public function test_modelo_eloquent_permite_los_campos_de_retry_de_automatizacion(): void
    {
        $model =
            new DteDocumentEloquentModel();

        $fillable =
            $model->getFillable();

        $this->assertContains(
            'automation_retry_action',
            $fillable
        );

        $this->assertContains(
            'automation_retry_count',
            $fillable
        );

        $this->assertContains(
            'automation_next_retry_at',
            $fillable
        );
    }

    public function test_repositorio_create_y_update_persisten_los_campos_de_retry(): void
    {
        $reflection =
            new ReflectionClass(
                EloquentDteDocumentRepository::class
            );

        /*
        |--------------------------------------------------------------------------
        | CREATE
        |--------------------------------------------------------------------------
        */

        $createMethod =
            $reflection->getMethod(
                'create'
            );

        $lines =
            file(
                $createMethod->getFileName()
            );

        $this->assertIsArray(
            $lines
        );

        $createSource =
            implode(
                '',
                array_slice(
                    $lines,
                    $createMethod->getStartLine() - 1,
                    $createMethod->getEndLine()
                        - $createMethod->getStartLine()
                        + 1
                )
            );

        $compactCreateSource =
            preg_replace(
                '/\s+/',
                '',
                $createSource
            );

        $this->assertIsString(
            $compactCreateSource
        );

        $this->assertStringContainsString(
            '\'automation_retry_action\'=>$document->automationRetryAction()',
            $compactCreateSource
        );

        $this->assertStringContainsString(
            '\'automation_retry_count\'=>$document->automationRetryCount()',
            $compactCreateSource
        );

        $this->assertStringContainsString(
            '\'automation_next_retry_at\'=>$document->automationNextRetryAt()',
            $compactCreateSource
        );

        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */

        $updateMethod =
            $reflection->getMethod(
                'update'
            );

        $updateSource =
            implode(
                '',
                array_slice(
                    $lines,
                    $updateMethod->getStartLine() - 1,
                    $updateMethod->getEndLine()
                        - $updateMethod->getStartLine()
                        + 1
                )
            );

        $compactUpdateSource =
            preg_replace(
                '/\s+/',
                '',
                $updateSource
            );

        $this->assertIsString(
            $compactUpdateSource
        );

        $this->assertStringContainsString(
            '\'automation_retry_action\'=>$document->automationRetryAction()',
            $compactUpdateSource
        );

        $this->assertStringContainsString(
            '\'automation_retry_count\'=>$document->automationRetryCount()',
            $compactUpdateSource
        );

        $this->assertStringContainsString(
            '\'automation_next_retry_at\'=>$document->automationNextRetryAt()',
            $compactUpdateSource
        );
    }

    public function test_mapper_reconstruye_el_retry_de_automatizacion_desde_eloquent(): void
    {
        $reflection =
            new ReflectionClass(
                DteDocumentPersistenceMapper::class
            );

        $method =
            $reflection->getMethod(
                'toDomain'
            );

        $lines =
            file(
                $method->getFileName()
            );

        $this->assertIsArray(
            $lines
        );

        $source =
            implode(
                '',
                array_slice(
                    $lines,
                    $method->getStartLine() - 1,
                    $method->getEndLine()
                        - $method->getStartLine()
                        + 1
                )
            );

        $compactSource =
            preg_replace(
                '/\s+/',
                '',
                $source
            );

        $this->assertIsString(
            $compactSource
        );

        $this->assertStringContainsString(
            'automationRetryAction:$model->automation_retry_action',
            $compactSource
        );

        $this->assertStringContainsString(
            'automationRetryCount:(int)$model->automation_retry_count',
            $compactSource
        );

        $this->assertStringContainsString(
            'automationNextRetryAt:',
            $compactSource
        );

        $this->assertStringContainsString(
            '$model->automation_next_retry_at',
            $compactSource
        );
    }
}