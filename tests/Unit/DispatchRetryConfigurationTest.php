<?php

namespace Tests\Unit;

use Tests\TestCase;

final class DispatchRetryConfigurationTest extends TestCase
{
    public function test_existe_una_politica_explicita_de_retry_para_dispatches_failed(): void
    {
        $this->assertIsInt(
            config('dte.automation.dispatch_retry.max_attempts')
        );

        $this->assertGreaterThan(
            0,
            config('dte.automation.dispatch_retry.max_attempts')
        );

        $backoff = config(
            'dte.automation.dispatch_retry.backoff_seconds'
        );

        $this->assertIsArray($backoff);
        $this->assertNotEmpty($backoff);

        foreach ($backoff as $seconds) {
            $this->assertIsInt($seconds);
            $this->assertGreaterThan(0, $seconds);
        }
    }
}