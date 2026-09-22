<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\Repositories\EloquentSiiDispatchRepository;
use ReflectionClass;
use Tests\TestCase;

final class EloquentSiiDispatchRepositoryTest extends TestCase
{
    public function test_find_by_id_for_update_expone_el_contrato_y_usa_lock_for_update(): void
    {
        $interfaceReflection = new ReflectionClass(
            SiiDispatchRepositoryInterface::class
        );

        $this->assertTrue(
            $interfaceReflection->hasMethod(
                'findByIdForUpdate'
            ),
            'SiiDispatchRepositoryInterface debe exponer findByIdForUpdate().'
        );

        $repositoryReflection = new ReflectionClass(
            EloquentSiiDispatchRepository::class
        );

        $this->assertTrue(
            $repositoryReflection->hasMethod(
                'findByIdForUpdate'
            ),
            'EloquentSiiDispatchRepository debe implementar findByIdForUpdate().'
        );

        $method = $repositoryReflection->getMethod(
            'findByIdForUpdate'
        );

        $lines = file(
            $method->getFileName()
        );

        $this->assertIsArray(
            $lines
        );

        $methodSource = implode(
            '',
            array_slice(
                $lines,
                $method->getStartLine() - 1,
                $method->getEndLine() - $method->getStartLine() + 1
            )
        );

        $this->assertStringContainsString(
            '->lockForUpdate()',
            $methodSource,
            'findByIdForUpdate() debe utilizar bloqueo pesimista lockForUpdate().'
        );

        $this->assertStringContainsString(
            "->where('id', \$id)",
            $methodSource,
            'findByIdForUpdate() debe bloquear específicamente el dispatch solicitado.'
        );

        $this->assertStringContainsString(
            '->first()',
            $methodSource
        );

        $this->assertStringContainsString(
            '$this->mapper->toDomain($model)',
            $methodSource,
            'El modelo bloqueado debe convertirse a la entidad de dominio.'
        );
    }
}