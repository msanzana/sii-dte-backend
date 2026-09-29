<?php

namespace Tests\Unit;

use Tests\TestCase;

final class SendUseCasesUploadRejectedGuardContractTest extends TestCase
{
    public function test_factura_bloquea_nuevo_upload_si_el_ultimo_dispatch_fue_upload_rejected(): void
    {
        $blockingStatuses = $this->obtenerBloqueBlockingStatuses(
            'Modules/Dte/Application/UseCases/Dispatch/SendSignedDteToSiiUseCase.php'
        );

        $this->assertStringContainsString(
            'DispatchStatus::UPLOAD_REJECTED',
            $blockingStatuses,
            'Factura debe bloquear un nuevo upload cuando el último dispatch quedó UPLOAD_REJECTED.'
        );
    }

    public function test_boleta_bloquea_nuevo_upload_si_el_ultimo_dispatch_fue_upload_rejected(): void
    {
        $blockingStatuses = $this->obtenerBloqueBlockingStatuses(
            'Modules/Dte/Application/UseCases/Dispatch/SendSignedBoletaToSiiUseCase.php'
        );

        $this->assertStringContainsString(
            'DispatchStatus::UPLOAD_REJECTED',
            $blockingStatuses,
            'Boleta debe bloquear un nuevo upload cuando el último dispatch quedó UPLOAD_REJECTED.'
        );
    }

    private function obtenerBloqueBlockingStatuses(string $relativePath): string
    {
        $source = file_get_contents(
            app_path($relativePath)
        );

        $this->assertNotFalse($source);

        $start = strpos(
            $source,
            '$blockingStatuses = ['
        );

        $this->assertNotFalse(
            $start,
            "No se encontró \$blockingStatuses en {$relativePath}."
        );

        $end = strpos(
            $source,
            '];',
            $start
        );

        $this->assertNotFalse(
            $end,
            "No se encontró el cierre de \$blockingStatuses en {$relativePath}."
        );

        return substr(
            $source,
            $start,
            ($end - $start) + 2
        );
    }
    public function test_factura_bloquea_nuevo_upload_si_el_ultimo_dispatch_esta_failed(): void
    {
        $source = file_get_contents(
            app_path(
                'Modules/Dte/Application/UseCases/Dispatch/SendSignedDteToSiiUseCase.php'
            )
        );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            'DispatchStatus::FAILED',
            $source,
            'Factura debe tratar FAILED como bloqueante por defecto.'
        );
    }

    public function test_boleta_bloquea_nuevo_upload_si_el_ultimo_dispatch_esta_failed(): void
    {
        $source = file_get_contents(
            app_path(
                'Modules/Dte/Application/UseCases/Dispatch/SendSignedBoletaToSiiUseCase.php'
            )
        );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            'DispatchStatus::FAILED',
            $source,
            'Boleta debe tratar FAILED como bloqueante por defecto.'
        );
    }
    public function test_factura_bloquea_failed_en_el_guard_de_duplicados(): void
    {
        $reflection = new \ReflectionClass(
            \App\Modules\Dte\Application\UseCases\Dispatch\SendSignedDteToSiiUseCase::class
        );

        $method = $reflection->getMethod(
            'assertNoUnresolvedDispatch'
        );

        $lines = file($method->getFileName());

        $this->assertIsArray($lines);

        $source = implode(
            '',
            array_slice(
                $lines,
                $method->getStartLine() - 1,
                $method->getEndLine() - $method->getStartLine() + 1
            )
        );

        $this->assertStringContainsString(
            'DispatchStatus::FAILED',
            $source,
            'FAILED debe ser bloqueante por defecto en Factura.'
        );
    }

    public function test_boleta_bloquea_failed_en_el_guard_de_duplicados(): void
    {
        $reflection = new \ReflectionClass(
            \App\Modules\Dte\Application\UseCases\Dispatch\SendSignedBoletaToSiiUseCase::class
        );

        $method = $reflection->getMethod(
            'assertNoUnresolvedDispatch'
        );

        $lines = file($method->getFileName());

        $this->assertIsArray($lines);

        $source = implode(
            '',
            array_slice(
                $lines,
                $method->getStartLine() - 1,
                $method->getEndLine() - $method->getStartLine() + 1
            )
        );

        $this->assertStringContainsString(
            'DispatchStatus::FAILED',
            $source,
            'FAILED debe ser bloqueante por defecto en Boleta.'
        );
    }
    public function test_factura_permite_un_failed_solo_si_es_un_retry_programado_y_vencido(): void
    {
        $reflection = new \ReflectionClass(
            \App\Modules\Dte\Application\UseCases\Dispatch\SendSignedDteToSiiUseCase::class
        );

        $method = $reflection->getMethod(
            'assertNoUnresolvedDispatch'
        );

        $lines = file($method->getFileName());

        $this->assertIsArray($lines);

        $source = implode(
            '',
            array_slice(
                $lines,
                $method->getStartLine() - 1,
                $method->getEndLine() - $method->getStartLine() + 1
            )
        );

        $compactSource = preg_replace(
            '/\s+/',
            '',
            $source
        );

        $this->assertIsString($compactSource);

        $this->assertStringContainsString(
            'newDispatchRetryPolicyService(',
            $compactSource
        );

        $this->assertStringContainsString(
            '$retryPolicy->canExecuteScheduledRetry(',
            $compactSource
        );

        $this->assertStringContainsString(
            '$latestDispatch,now()->toDateTimeImmutable()',
            $compactSource
        );

        $this->assertStringContainsString(
            'DispatchStatus::FAILED',
            $source,
            'FAILED debe continuar bloqueado por defecto.'
        );
    }

    public function test_boleta_permite_un_failed_solo_si_es_un_retry_programado_y_vencido(): void
    {
        $reflection = new \ReflectionClass(
            \App\Modules\Dte\Application\UseCases\Dispatch\SendSignedBoletaToSiiUseCase::class
        );

        $method = $reflection->getMethod(
            'assertNoUnresolvedDispatch'
        );

        $lines = file($method->getFileName());

        $this->assertIsArray($lines);

        $source = implode(
            '',
            array_slice(
                $lines,
                $method->getStartLine() - 1,
                $method->getEndLine() - $method->getStartLine() + 1
            )
        );

        $compactSource = preg_replace(
            '/\s+/',
            '',
            $source
        );

        $this->assertIsString($compactSource);

        $this->assertStringContainsString(
            'newDispatchRetryPolicyService(',
            $compactSource
        );

        $this->assertStringContainsString(
            '$retryPolicy->canExecuteScheduledRetry(',
            $compactSource
        );

        $this->assertStringContainsString(
            '$latestDispatch,now()->toDateTimeImmutable()',
            $compactSource
        );

        $this->assertStringContainsString(
            'DispatchStatus::FAILED',
            $source,
            'FAILED debe continuar bloqueado por defecto.'
        );
    }
    public function test_factura_hereda_retry_count_del_failed_programado_al_nuevo_dispatch(): void
    {
        $reflection = new \ReflectionClass(
            \App\Modules\Dte\Application\UseCases\Dispatch\SendSignedDteToSiiUseCase::class
        );

        $guard = $reflection->getMethod(
            'assertNoUnresolvedDispatch'
        );

        $returnType = $guard->getReturnType();

        $this->assertInstanceOf(
            \ReflectionNamedType::class,
            $returnType
        );

        $this->assertSame(
            'int',
            $returnType->getName()
        );

        $lines = file($guard->getFileName());

        $this->assertIsArray($lines);

        $guardSource = implode(
            '',
            array_slice(
                $lines,
                $guard->getStartLine() - 1,
                $guard->getEndLine() - $guard->getStartLine() + 1
            )
        );

        $this->assertStringContainsString(
            'return $latestDispatch->retryCount();',
            $guardSource
        );

        $execute = $reflection->getMethod('execute');

        $executeSource = implode(
            '',
            array_slice(
                $lines,
                $execute->getStartLine() - 1,
                $execute->getEndLine() - $execute->getStartLine() + 1
            )
        );

        $compactSource = preg_replace(
            '/\s+/',
            '',
            $executeSource
        );

        $this->assertIsString($compactSource);

        $this->assertStringContainsString(
            '$retryCount=$this->assertNoUnresolvedDispatch(',
            $compactSource
        );

        $this->assertStringContainsString(
            'retryCount:$retryCount',
            $compactSource
        );
    }

    public function test_boleta_hereda_retry_count_del_failed_programado_y_rsc_parte_en_cero(): void
    {
        $reflection = new \ReflectionClass(
            \App\Modules\Dte\Application\UseCases\Dispatch\SendSignedBoletaToSiiUseCase::class
        );

        $guard = $reflection->getMethod(
            'assertNoUnresolvedDispatch'
        );

        $returnType = $guard->getReturnType();

        $this->assertInstanceOf(
            \ReflectionNamedType::class,
            $returnType
        );

        $this->assertSame(
            'int',
            $returnType->getName()
        );

        $lines = file($guard->getFileName());

        $this->assertIsArray($lines);

        $guardSource = implode(
            '',
            array_slice(
                $lines,
                $guard->getStartLine() - 1,
                $guard->getEndLine() - $guard->getStartLine() + 1
            )
        );

        $compactGuardSource = preg_replace(
            '/\s+/',
            '',
            $guardSource
        );

        $this->assertIsString($compactGuardSource);

        $this->assertStringContainsString(
            'if($isControlledRscReprocess){return0;}',
            $compactGuardSource
        );

        $this->assertStringContainsString(
            'return$latestDispatch->retryCount();',
            $compactGuardSource
        );

        $execute = $reflection->getMethod('execute');

        $executeSource = implode(
            '',
            array_slice(
                $lines,
                $execute->getStartLine() - 1,
                $execute->getEndLine() - $execute->getStartLine() + 1
            )
        );

        $compactExecuteSource = preg_replace(
            '/\s+/',
            '',
            $executeSource
        );

        $this->assertIsString($compactExecuteSource);

        $this->assertStringContainsString(
            '$retryCount=$this->assertNoUnresolvedDispatch($document);',
            $compactExecuteSource
        );

        $this->assertStringContainsString(
            'retryCount:$retryCount',
            $compactExecuteSource
        );
    }
}