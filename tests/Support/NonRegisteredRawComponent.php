<?php
declare(strict_types=1);

namespace ErickComp\RawBladeComponents\Tests\Support;

use Illuminate\View\Component as BladeComponent;
class NonRegisteredRawComponent extends BladeComponent
{
    public const CONTENT = 'X-NON-REGISTERED';

    public function render()
    {
        return fn() => self::CONTENT;
    }
}
