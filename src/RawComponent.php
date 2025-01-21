<?php

namespace ErickComp\RawBladeComponents;

use \Illuminate\Support\ServiceProvider;

class RawComponent extends ServiceProvider
{
    public function __construct(
        protected string $tag,
        protected string $openingCode,
        protected string $closingCode,
        protected ?string $selfClosingCode = null
    )
    {}
}
