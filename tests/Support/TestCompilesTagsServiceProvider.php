<?php

declare(strict_types=1);

namespace ErickComp\RawBladeComponents\Tests\Support;

use ErickComp\RawBladeComponents\RawComponent;
use Illuminate\Support\ServiceProvider;

class TestCompilesTagsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        RawComponent::rawComponent(
            'x-test-full',
            static::openingCode(),
            static::closingCode(),
            static::selfClosingCode(),
        );

        RawComponent::rawComponent(
            'x-test-no-self-closing',
            static::openingCode(),
            static::closingCode(),
        );

        RawComponent::rawComponentStartingWith(
            'x-test-starting-with',
            static::openingCode() . ":starting-with-START",
            static::closingCode() . ":starting-with-END",
            static::selfClosingCode() . ":starting-with-SELF-CLOSE",
        );

        RawComponent::rawComponentStartingWith(
            'x-test-starting-with-no-closing',
            static::openingCode() . ":starting-with-START",
            static::closingCode() . ":starting-with-END",
        );
    }

    public static function openingCode(): string
    {
        return "RAW-COMPONENT-START";
    }

    public static function closingCode(): string
    {
        return "RAW-COMPONENT-END";
    }

    public static function selfClosingCode(): string
    {
        return static::openingCode() . '|' . static::closingCode();
    }
}
