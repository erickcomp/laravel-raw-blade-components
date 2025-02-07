<?php

declare(strict_types=1);

namespace ErickComp\RawBladeComponents\Tests;

use ErickComp\RawBladeComponents\RawBladeComponentsServiceProvider;
use ErickComp\RawBladeComponents\Tests\Support\TestCompilesTagsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            RawBladeComponentsServiceProvider::class,
            TestCompilesTagsServiceProvider::class,
        ];
    }
}
