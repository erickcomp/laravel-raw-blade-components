<?php

namespace ErickComp\RawBladeComponents;

class RawComponentCode
{
    public function __construct(
        public string $tag,
        public string $openingCode,
        public string $closingCode,
        public ?string $selfClosingCode = null,
        public array $defaultAttributes = [],
    ) {}
}
