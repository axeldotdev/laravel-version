<?php

namespace Axeldotdev\LaravelVersion;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Axeldotdev\LaravelVersion\Commands\UpdateAppVersion;

class LaravelVersionServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-version')
            ->hasConfigFile()
            ->hasCommand(UpdateAppVersion::class);
    }
}
