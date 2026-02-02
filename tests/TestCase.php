<?php

namespace Knobik\SqlAgent\Tests;

use Knobik\SqlAgent\SqlAgentServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            SqlAgentServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'SqlAgent' => \Knobik\SqlAgent\Facades\SqlAgent::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }
}
