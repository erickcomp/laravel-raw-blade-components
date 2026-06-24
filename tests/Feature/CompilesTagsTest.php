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

it('can compile non-self-closing tags starting with using suffixed closing tag', function () {
    $rendered = Blade::render('<x-test-starting-with:0101>|</x-test-starting-with:0101>', deleteCachedView: true);
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
    );
});

it('preserves __rawComponentTagPrefix in stack for exact self-closing tags', function () {
    /** @var \ErickComp\RawBladeComponents\RawComponentsManager */
    $manager = \ErickComp\RawBladeComponents\RawComponent::getFacadeRoot();

    $compiled = $manager->compileRawBladeComponents('<x-test-full />');
    expect($compiled)->toContain("'__rawComponentTagPrefix'");
});

it('merges default attributes on self-closing prefix-components', function () {
    \ErickComp\RawBladeComponents\RawComponent::rawComponentStartingWith(
        'x-test-sc-defaults',
        '<open>',
        '</close>',
        '<?php echo $__rawComponentAttributes; ?>',
        ['data-default' => 'yes'],
    );

    $rendered = Blade::render('<x-test-sc-defaults:foo />', deleteCachedView: true);
    expect($rendered)->toContain('data-default="yes"');
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

it('can compile nested exact components', function () {
    \ErickComp\RawBladeComponents\RawComponent::rawComponent('x-nest-outer', '[OUTER-OPEN]', '[OUTER-CLOSE]');
    \ErickComp\RawBladeComponents\RawComponent::rawComponent('x-nest-inner', '[INNER-OPEN]', '[INNER-CLOSE]');

    $rendered = Blade::render(
        '<x-nest-outer><x-nest-inner>content</x-nest-inner></x-nest-outer>',
        deleteCachedView: true,
    );

    expect($rendered)->toContain('[OUTER-OPEN]');
    expect($rendered)->toContain('[INNER-OPEN]content[INNER-CLOSE]');
    expect($rendered)->toContain('[OUTER-CLOSE]');
});

it('can compile self-closing exact inside opening/closing exact', function () {
    \ErickComp\RawBladeComponents\RawComponent::rawComponent('x-nest-wrap', '[WRAP-OPEN]', '[WRAP-CLOSE]');
    \ErickComp\RawBladeComponents\RawComponent::rawComponent('x-nest-sc', '[SC-OPEN]', '[SC-CLOSE]', '[SC-SELF]');

    $rendered = Blade::render(
        '<x-nest-wrap>before<x-nest-sc />after</x-nest-wrap>',
        deleteCachedView: true,
    );

    expect($rendered)->toContain('[WRAP-OPEN]');
    expect($rendered)->toContain('before');
    expect($rendered)->toContain('[SC-SELF]');
    expect($rendered)->toContain('after');
    expect($rendered)->toContain('[WRAP-CLOSE]');
});

it('can compile exact component nested inside prefix-component', function () {
    \ErickComp\RawBladeComponents\RawComponent::rawComponent('x-nest-child', '[CHILD-OPEN]', '[CHILD-CLOSE]');
    \ErickComp\RawBladeComponents\RawComponent::rawComponentStartingWith('x-nest-pfx', '[PFX-OPEN]', '[PFX-CLOSE]');

    $rendered = Blade::render(
        '<x-nest-pfx:abc><x-nest-child>content</x-nest-child></x-nest-pfx:abc>',
        deleteCachedView: true,
    );

    expect($rendered)->toContain('[PFX-OPEN]');
    expect($rendered)->toContain('[CHILD-OPEN]content[CHILD-CLOSE]');
    expect($rendered)->toContain('[PFX-CLOSE]');
});

it('matches longer prefix before shorter when prefixes overlap', function () {
    \ErickComp\RawBladeComponents\RawComponent::rawComponentStartingWith('x-overlap', '[SHORT-OPEN]', '[SHORT-CLOSE]');
    \ErickComp\RawBladeComponents\RawComponent::rawComponentStartingWith('x-overlap-specific', '[LONG-OPEN]', '[LONG-CLOSE]');

    $rendered = Blade::render(
        '<x-overlap-specific:foo>content</x-overlap-specific:foo>',
        deleteCachedView: true,
    );

    expect($rendered)->toContain('[LONG-OPEN]');
    expect($rendered)->toContain('[LONG-CLOSE]');
    expect($rendered)->not->toContain('[SHORT-OPEN]');
    expect($rendered)->not->toContain('[SHORT-CLOSE]');
});

it('overwrites registration when same tag is registered twice', function () {
    \ErickComp\RawBladeComponents\RawComponent::rawComponent('x-test-overwrite', '[FIRST-OPEN]', '[FIRST-CLOSE]');
    \ErickComp\RawBladeComponents\RawComponent::rawComponent('x-test-overwrite', '[SECOND-OPEN]', '[SECOND-CLOSE]');

    $rendered = Blade::render('<x-test-overwrite>content</x-test-overwrite>', deleteCachedView: true);

    expect($rendered)->toContain('[SECOND-OPEN]');
    expect($rendered)->toContain('[SECOND-CLOSE]');
    expect($rendered)->not->toContain('[FIRST-OPEN]');
    expect($rendered)->not->toContain('[FIRST-CLOSE]');
});

it('does not compile non-registered tags', function () {
    Blade::component(NonRegisteredRawComponent::class, 'non-registered-raw');
    $bladeStringWithNonRegisteredRawComponents = '<x-non-registered-raw></x-non-registered-raw> <x-non-registered-raw />';
    $rendered = Blade::render($bladeStringWithNonRegisteredRawComponents, deleteCachedView: true);

    expect($rendered)->toBe(NonRegisteredRawComponent::CONTENT . ' ' . NonRegisteredRawComponent::CONTENT);
});


it('renders default attributes on non-self-closing tags', function () {
    \ErickComp\RawBladeComponents\RawComponent::rawComponent(
        'x-test-def-attrs',
        '<?php echo $__rawComponentAttributes; ?>',
        '</div>',
        null,
        ['class' => 'default-class', 'data-role' => 'widget'],
    );

    $rendered = Blade::render('<x-test-def-attrs>content</x-test-def-attrs>', deleteCachedView: true);

    expect($rendered)->toContain('class="default-class"');
    expect($rendered)->toContain('data-role="widget"');
});

it('tag attributes override default attributes', function () {
    \ErickComp\RawBladeComponents\RawComponent::rawComponent(
        'x-test-override-attrs',
        '<?php echo $__rawComponentAttributes; ?>',
        '</div>',
        null,
        ['class' => 'default'],
    );

    $rendered = Blade::render('<x-test-override-attrs class="custom">content</x-test-override-attrs>', deleteCachedView: true);

    expect($rendered)->toContain('class="custom"');
    expect($rendered)->not->toContain('class="default"');
});

it('renders attributes on non-self-closing tags', function () {
    \ErickComp\RawBladeComponents\RawComponent::rawComponent(
        'x-test-attrs',
        '<?php echo $__rawComponentAttributes; ?>',
        '</div>',
    );

    $rendered = Blade::render('<x-test-attrs class="foo" id="bar">content</x-test-attrs>', deleteCachedView: true);

    expect($rendered)->toContain('class="foo"');
    expect($rendered)->toContain('id="bar"');
    expect($rendered)->toContain('content');
});

it('renders attributes on self-closing tags', function () {
    \ErickComp\RawBladeComponents\RawComponent::rawComponent(
        'x-test-attrs-sc',
        '<div>',
        '</div>',
        '<?php echo $__rawComponentAttributes; ?>',
    );

    $rendered = Blade::render('<x-test-attrs-sc class="baz" />', deleteCachedView: true);

    expect($rendered)->toContain('class="baz"');
});
