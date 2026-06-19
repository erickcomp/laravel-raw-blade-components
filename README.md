# laravel-raw-blade-components

> Register raw Blade component tags and compile them into arbitrary opening / closing / self-closing code snippets.

[![PHP](https://img.shields.io/badge/php-%5E8.2-8892BF.svg)](https://www.php.net/) [![License: MIT](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE) [![Packagist Version](https://badge-placeholder.invalid/packagist)](https://packagist.org/) [![Tests](https://badge-placeholder.invalid/tests)](https://github.com/)

This tiny package allows you to register "raw" Blade component tags (for example `<x-your-tag>` or prefix tags such as `<x-prefix:...>`) and map them to arbitrary opening, closing and self-closing snippets that will be injected into templates at Blade compile time.

Table of contents
- Features
- Requirements
- Installation
- Configuration
- Usage (basic)
- Usage (advanced / prefix-matching)
- Public API
- Internals & Notes
- Testing
- Contributing
- License

## Features

- Register exact component tags and prefix-based tags and map them to arbitrary output snippets.
- Support for non-self-closing and self-closing usage.
- Preserves nested contexts using an internal stack so nested raw components work predictably.
- Uses Laravel's `ComponentTagCompiler` for attribute parsing and escaping semantics.
- Auto-discovered service provider — no manual provider registration required.

## Requirements

- PHP 8.2+
- Laravel 12.60+ or 13.10+

## Installation

Install with Composer (replace with real package name after publishing):

```bash
composer require erickcomp/laravel-raw-blade-components
```

The service provider is auto-discovered. If you need to register it manually, add the provider to `config/app.php`:

```php
ErickComp\RawBladeComponents\RawBladeComponentsServiceProvider::class,
```

## Configuration

There is no published configuration file. You register raw components programmatically from a service provider (for example in `App\Providers\AppServiceProvider::boot`).

## Usage (basic)

Register a component that replaces an `<x-...>` tag with custom opening/closing snippets:

```php
use ErickComp\RawBladeComponents\RawComponent;

RawComponent::rawComponent(
    'x-badge',
    '<span class="badge">',    // opening snippet
    '</span>',                   // closing snippet
    '<span class="badge" />',  // optional self-closing snippet
    ['class' => 'badge-default'] // optional default attributes
);
```

Now a Blade string like `<x-badge>Hi</x-badge>` will be compiled into your opening snippet + `Hi` + closing snippet.

## Usage (prefix-based / advanced matching)

Register components that match tags starting with a prefix (useful for namespaced tags or tags containing dynamic suffixes):

```php
RawComponent::rawComponentStartingWith(
    'x-alert',
    '<div class="alert">',
    '</div>',
    '<div class="alert" />'
);

// Matches tags such as <x-alert:success> and <x-alert:123>
```

Prefix registrations are sorted so longer/more-specific prefixes match first.

## Error cases

- Using a self-closing tag when no self-closing snippet was registered for that component will throw a `LogicException`, which is surfaced as an `Illuminate\View\ViewException` when rendering (see tests).

## Public API

- Facade: `ErickComp\RawBladeComponents\RawComponent`
  - `rawComponent(string $tag, string $openingCode, string $closingCode, ?string $selfClosingCode = null, array $defaultAttributes = [])`
  - `rawComponentStartingWith(string $tag, string $openingCode, string $closingCode, ?string $selfClosingCode = null, array $defaultAttributes = [])`
  - `compileRawBladeComponents(string $templateStr): string`

- Manager: `ErickComp\RawBladeComponents\RawComponentsManager` (registered as a singleton)

## Internals & notes

- The package registers its compiler via `Blade::prepareStringsForCompilationUsing(...)` in the service provider so it runs during Blade's compile process.
- Internally, the generated template code maintains a small stack using the following internal variables:
  - `$__rawComponentsStack`, `$__rawComponentTagPrefix`, `$__rawComponentTag`, `$__rawComponentAttributes`.

These variables are implementation details and should be considered private.

## Limitations

- No configuration, published assets, migrations or CLI commands are provided.
- Attribute handling follows Laravel's `ComponentTagCompiler`, but the test coverage for complex attribute/binding scenarios is limited — please validate attribute behavior in your application if you rely on bound attributes or complex directives.

## Testing

This package uses Pest and Orchestra Testbench for integration tests. Run the tests locally:

```bash
composer install --dev
./vendor/bin/pest
```

## Contributing

See `CONTRIBUTING.md` for contribution guidelines.

## License

MIT — see LICENSE file.

## Credits

Author and contributors are listed in the repository metadata.

