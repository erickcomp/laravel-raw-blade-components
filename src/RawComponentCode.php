<?php

namespace ErickComp\RawBladeComponents;

class RawComponentCode
{
    public function __construct(
        public readonly string $tag,
        public readonly string $openingCode,
        public readonly string $closingCode,
        public readonly ?string $selfClosingCode = null,
        public readonly array $defaultAttributes = [],
    ) {}
}
