<?php

namespace Axeldotdev\LaravelVersion;

use Axeldotdev\LaravelVersion\Commands\UpdateAppVersion;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
