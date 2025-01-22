<?php

namespace ErickComp\RawBladeComponents;

use \Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class RawBladeComponentsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(RawComponentsManager::class);
    }

    public function boot(RawComponentsManager $rawComponentsManager)
    {
        Blade::prepareStringsForCompilationUsing(
            $rawComponentsManager->compileRawBladeComponents(...),
        );
    }
}
