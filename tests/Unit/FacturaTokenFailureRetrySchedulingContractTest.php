<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\UseCases\Dispatch\SendSignedDteToSiiUseCase;
use ReflectionClass;
use Tests\TestCase;

final class FacturaTokenFailureRetrySchedulingContractTest extends TestCase
{
    public function test_fallo_de_token_programa_retry_antes_del_post(): void
    {
        $reflection = new ReflectionClass(
            SendSignedDteToSiiUseCase::class
        );

        $method = $reflection->getMethod(
            'execute'
        );

        $lines = file(
            $method->getFileName()
        );

        $this->assertIsArray(
            $lines
        );

        $source = implode(
            '',
            array_slice(
                $lines,
                $method->getStartLine() - 1,
                $method->getEndLine()
                    - $method->getStartLine()
                    + 1
            )
        );

        $compactSource = preg_replace(
            '/\s+/',
            '',
            $source
        );

        $this->assertIsString(
            $compactSource
        );

        $this->assertStringContainsString(
            '$failedDispatch=$dispatch->withStatus(',
            $compactSource
        );

        $this->assertStringContainsString(
            '$scheduleRetryService=newScheduleDispatchRetryService();',
            $compactSource
        );

        $this->assertStringContainsString(
            '$scheduleRetryService->canScheduleRetry($failedDispatch)',
            $compactSource
        );

        $this->assertStringContainsString(
            '$failedDispatch=$scheduleRetryService->execute($failedDispatch);',
            $compactSource
        );

        $this->assertStringContainsString(
            '$dispatch=$this->dispatchRepository->update($failedDispatch);',
            $compactSource
        );
        $this->assertStringContainsString(
            'throwDispatchRetryScheduledException::forScheduledRetry(',
            $compactSource
        );
        $this->assertStringContainsString(
            'throw$e;',
            $compactSource
        );

    }

    public function test_retry_automatico_se_programa_una_sola_vez_en_el_use_case(): void
    {
        $reflection = new ReflectionClass(
            SendSignedDteToSiiUseCase::class
        );

        $method = $reflection->getMethod(
            'execute'
        );

        $lines = file(
            $method->getFileName()
        );

        $this->assertIsArray(
            $lines
        );

        $source = implode(
            '',
            array_slice(
                $lines,
                $method->getStartLine() - 1,
                $method->getEndLine()
                    - $method->getStartLine()
                    + 1
            )
        );

        $compactSource = preg_replace(
            '/\s+/',
            '',
            $source
        );

        $this->assertIsString(
            $compactSource
        );

        $this->assertSame(
            1,
            substr_count(
                $compactSource,
                'newScheduleDispatchRetryService()'
            )
        );
    }
}