<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $connection = (string) config('database.default');

        $database = (string) config(
            "database.connections.{$connection}.database"
        );

        if (
            $connection !== 'sqlite'
            || $database !== ':memory:'
        ) {
            throw new RuntimeException(
                "SEGURIDAD TESTS: PHPUnit intentó usar "
                . "{$connection}/{$database}. "
                . "Los tests sólo pueden ejecutarse sobre sqlite :memory:."
            );
        }
    }
}