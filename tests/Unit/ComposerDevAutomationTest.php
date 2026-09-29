<?php

namespace Tests\Unit;

use Tests\TestCase;

final class ComposerDevAutomationTest extends TestCase
{
    public function test_composer_dev_inicia_el_scheduler_de_laravel(): void
    {
        $composer = json_decode(
            file_get_contents(base_path('composer.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $devScript = $composer['scripts']['dev'] ?? [];

        $this->assertIsArray($devScript);

        $joined = implode("\n", $devScript);

        $this->assertStringContainsString(
            'php artisan schedule:work',
            $joined
        );
    }
    public function test_composer_dev_escucha_las_colas_de_automatizacion_dte(): void
    {
        $composer = json_decode(
            file_get_contents(base_path('composer.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $devScript = $composer['scripts']['dev'] ?? [];

        $this->assertIsArray($devScript);

        $joined = implode("\n", $devScript);

        $this->assertStringContainsString(
            'php artisan queue:listen',
            $joined
        );

        $this->assertStringContainsString(
            '--queue=',
            $joined
        );

        foreach ([
            'dte-pipeline',
            'dte-dispatch-polling',
            'dte-document-status',
            'dte-maintenance',
        ] as $queue) {
            $this->assertStringContainsString(
                $queue,
                $joined
            );
        }
    }
}