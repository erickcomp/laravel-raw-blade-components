<?php

namespace ErickComp\RawBladeComponents;

use \Illuminate\Support\ServiceProvider;

class RawComponent extends ServiceProvider
{
    public function __construct(
        public string $tag,
        public string $openingCode,
        public string $closingCode,
        public ?string $selfClosingCode = null
    )
    {}
}
