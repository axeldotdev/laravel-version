<?php

namespace Axeldotdev\LaravelVersion\Tests;

use Axeldotdev\LaravelVersion\LaravelVersionServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [LaravelVersionServiceProvider::class];
    }
}
