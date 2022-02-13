<?php

namespace Axeldotdev\LaravelVersion\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Axeldotdev\LaravelVersion\LaravelVersion
 */
class LaravelVersion extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Axeldotdev\LaravelVersion\LaravelVersion::class;
    }
}
