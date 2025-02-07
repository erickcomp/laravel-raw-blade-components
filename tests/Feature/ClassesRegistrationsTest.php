<?php

use ErickComp\RawBladeComponents\RawComponent;
use ErickComp\RawBladeComponents\RawComponentsManager;
use Illuminate\Support\Facades\Facade;

it('registered RawComponentsManager class', function () {

    expect(app()->bound(RawComponentsManager::class))->toBe(true);
});

test('if RawComponentsManager class was registered as a singleton', function () {

    expect(app()->isShared(RawComponentsManager::class))->toBe(true);
});

test('if the RawComponent Facade is available', function () {

    expect(
        class_exists(RawComponent::class) &&
        \is_a(RawComponent::class, Facade::class, true) &&
        RawComponent::getFacadeRoot() instanceof RawComponentsManager

    )->toBe(true);
});

test('it can register a raw component', function () {
    $componentTag = 'x-test-register';
    /** @var RawComponentsManager */
    $manager = RawComponent::getFacadeRoot();

    $manager->rawComponent(
        $componentTag,
        'opening',
        'closing',
    );

    $refObject = new \ReflectionObject($manager);

    /** @var \Illuminate\Support\Collection */
    $components = $refObject->getProperty('rawComponents')->getValue($manager);

    expect($components->has($componentTag))->toBe(true);
});

test('TODO: it can register an attributeless raw component', function () { })->todo();

