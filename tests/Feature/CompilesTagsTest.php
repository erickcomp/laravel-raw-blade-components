<?php

use ErickComp\RawBladeComponents\Tests\Support\TestCompilesTagsServiceProvider;
use Illuminate\Support\Facades\Blade;
use ErickComp\RawBladeComponents\Tests\Support\NonRegisteredRawComponent;

it('can compile non-self-closing tags', function () {
    $rendered = Blade::render('<x-test-full>|</x-test-full>', deleteCachedView: true);
    expect($rendered)->toBe(TestCompilesTagsServiceProvider::openingCode() . '|' . TestCompilesTagsServiceProvider::closingCode() . PHP_EOL);
});

it('can compile self-closing tags', function () {
    $rendered = Blade::render('<x-test-full />', deleteCachedView: true);
    expect($rendered)->toBe(TestCompilesTagsServiceProvider::selfClosingCode() . PHP_EOL);
});

it('can compile non-self-closing tags starting with', function () {
    $rendered = Blade::render('<x-test-starting-with:0101>|</x-test-starting-with>', deleteCachedView: true);
    expect($rendered)->toBe(
        ''
        . TestCompilesTagsServiceProvider::openingCode() . ':starting-with-START'
        . '|'
        . TestCompilesTagsServiceProvider::closingCode() . ':starting-with-END'
        . PHP_EOL
    );
});

it('can compile self-closing tags starting with', function () {
    $rendered = Blade::render('<x-test-starting-with:0101 />', deleteCachedView: true);
    expect($rendered)->toBe(
        ''
        . TestCompilesTagsServiceProvider::selfClosingCode() . ":starting-with-SELF-CLOSE"
        . PHP_EOL
    );
});

test('it fails to compile self-closing tags when no self-tag code was registered', function () {

    $exception = null;

    //Blade::render('<x-test-no-self-closing />', deleteCachedView: true);

    $test = new class () {
        public ?string $tag = null;

        public ?\Throwable $exception = null;

        public function __invoke()
        {
            try {
                $toRender = "< {$this->tag} />";
                Blade::render($toRender, deleteCachedView: true);
            } catch (\Throwable $e) {
                $this->exception = $e;

                throw $e;
            }
        }
    };

    $regexNoClosingTag = '/The component \[.+\] is not meant to be used with the self-closing tag syntax/i';

    $test->tag = 'x-test-no-self-closing';
    expect($test)->toThrow(\Illuminate\View\ViewException::class);
    expect($test->exception->getPrevious())->toBeInstanceOf(\LogicException::class);
    expect($test->exception->getPrevious()->getMessage())->toMatch($regexNoClosingTag);

    $test->tag = 'x-test-starting-with-no-closing:001';
    expect($test)->toThrow(\Illuminate\View\ViewException::class);
    expect($test->exception->getPrevious())->toBeInstanceOf(\LogicException::class);
    expect($test->exception->getPrevious()->getMessage())->toMatch($regexNoClosingTag);
});

it('does not compile non-registered tags', function () {
    Blade::component(NonRegisteredRawComponent::class, 'non-registered-raw');
    $bladeStringWithNonRegisteredRawComponents = '<x-non-registered-raw></x-non-registered-raw> <x-non-registered-raw />';
    $rendered = Blade::render($bladeStringWithNonRegisteredRawComponents, deleteCachedView: true);

    expect($rendered)->toBe(NonRegisteredRawComponent::CONTENT . ' ' . NonRegisteredRawComponent::CONTENT);
});


test('TODO: if the raw component can use attributes', function () {
})->todo();
