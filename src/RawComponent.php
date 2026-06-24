<?php

namespace ErickComp\RawBladeComponents;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \ErickComp\RawBladeComponents\RawComponentsManager rawComponent(string $tag, string $openingCode, string $closingCode, ?string $selfClosingCode = null, array $defaultAttributes = [])
 * @method static \ErickComp\RawBladeComponents\RawComponentsManager rawComponentStartingWith(string $tag, string $openingCode, string $closingCode, ?string $selfClosingCode = null, array $defaultAttributes = [])
 * @method static string compileRawBladeComponents(string $templateStr)
 */
class RawComponent extends Facade
{
    protected static function getFacadeAccessor()
    {
        return RawComponentsManager::class;
    }
}
