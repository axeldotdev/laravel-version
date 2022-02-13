<?php

namespace Axeldotdev\LaravelVersion\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Axeldotdev\LaravelVersion\LaravelVersionServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [LaravelVersionServiceProvider::class];
    }
}
