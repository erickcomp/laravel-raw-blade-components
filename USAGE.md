# Usage

This document expands on usage examples and explains implementation details that may be useful for advanced usage or debugging.

## Registering raw components

You register components programmatically via the `RawComponent` facade. A typical registration sits in a service provider's `boot()` method:

```php
use ErickComp\RawBladeComponents\RawComponent;

public function boot()
{
    RawComponent::rawComponent(
        'x-hello',
        '<div class="hello">',
        '</div>',
        '<div class="hello" />',
        ['class' => 'hello-default']
    );
}
```

## How tags are matched

- Exact registrations: a registration for `'x-hello'` will match exactly `<x-hello>` tags.
- Prefix registrations: `rawComponentStartingWith('x-alert', ...)` will match any tag where the component tag string begins with `x-alert` (for example `<x-alert:success>`).
- Prefixes are sorted so more specific (longer) prefixes match before shorter ones.

## Attributes

The package uses Laravel's `ComponentTagCompiler` internals to parse attributes. This means attribute binding semantics, `:attribute` bound values and Blade's `@class` / `@style` directives behave consistently with Laravel components insofar as `ComponentTagCompiler` supports them.

All parsed attributes (from both the tag and the defaults) are available inside your snippets via `$__rawComponentAttributes`, which is an `Illuminate\View\ComponentAttributeBag` instance — the same class used by standard Blade components.

### Default attributes

You can provide default attribute values when registering a component. Defaults are merged with attributes found in the tag at compile time. When both define the same key, the **tag attribute wins** (overrides the default).

```php
RawComponent::rawComponent(
    'x-alert',
    '<div <?php echo $__rawComponentAttributes; ?>>',
    '</div>',
    null,
    ['class' => 'alert', 'role' => 'alert'],
);
```

```blade
{{-- Defaults only --}}
<x-alert>Warning!</x-alert>
{{-- renders: <div class="alert" role="alert">Warning!</div> --}}

{{-- Tag attribute overrides 'class' default, 'role' default is kept --}}
<x-alert class="alert alert-danger">Error!</x-alert>
{{-- renders: <div class="alert alert-danger" role="alert">Error!</div> --}}
```

### Accepted value types for default attributes

| Value | Example | Behavior |
|---|---|---|
| Plain string | `'badge'` | Rendered as HTML attribute value: `attr="badge"` |
| Numeric | `42` | Rendered as-is: `attr="42"` |
| Boolean `true` | `true` | Rendered as boolean HTML attribute: `attr` |
| PHP expression | `"'prefix-' . \$var"` | Evaluated at runtime (advanced usage for dynamic defaults) |

Plain strings are the most common case and are normalized automatically by the package — you do not need to worry about internal formatting.

### Advanced: using `$__rawComponentAttributes` methods

Since `$__rawComponentAttributes` is a `ComponentAttributeBag`, you can call any of its methods in your snippets:

```php
RawComponent::rawComponent(
    'x-btn',
    '<button <?php echo $__rawComponentAttributes->merge(["type" => "button"]); ?>>',
    '</button>',
);
```

```blade
<x-btn class="btn-primary">Click</x-btn>
{{-- renders: <button type="button" class="btn-primary">Click</button> --}}

## Template variables available in snippets

When a raw component is compiled, the following PHP variables are available inside your opening, closing and self-closing code snippets:

### Current component context

- `$__rawComponentTagPrefix` — the registered prefix for prefix-matched components (empty string for exact matches).
- `$__rawComponentTag` — the actual component tag matched (for example `x-test-starting-with:0101`).
- `$__rawComponentAttributes` — an instance of `Illuminate\View\ComponentAttributeBag` created from merged defaults and parsed attributes.

### Parent component context (nesting)

When raw components are nested, these variables give you access to the enclosing component's context:

- `$__parentRawComponentTagPrefix` — the `$__rawComponentTagPrefix` of the parent raw component (`null` if not nested).
- `$__parentRawComponentTag` — the `$__rawComponentTag` of the parent raw component (`null` if not nested).
- `$__parentRawComponentAttributes` — the `$__rawComponentAttributes` of the parent raw component (`null` if not nested).

Example — conditionally adding a class based on the parent component:

```php
RawComponent::rawComponent(
    'x-card',
    '<div class="card">',
    '</div>',
);

RawComponent::rawComponent(
    'x-card-title',
    '<?php $class = $__parentRawComponentTag === "x-card" ? "card-title" : "title"; ?><h2 class="<?php echo $class; ?>">',
    '</h2>',
);
```

```blade
<x-card>
    <x-card-title>Hello</x-card-title>  {{-- renders with class="card-title" --}}
</x-card>
<x-card-title>World</x-card-title>      {{-- renders with class="title" --}}
```

### Internal variables

- `$__rawComponentsStack` — an array stack used internally to preserve and restore context when nesting. This is an implementation detail — do not depend on it.

## Self-closing vs non-self-closing

- If a component was registered without a `selfClosingCode` and a template uses the self-closing syntax (`<x-name />`), the manager throws a `LogicException` indicating the component isn't meant to be used that way. This bubbles into a `Illuminate\View\ViewException` when rendering views.

## Debugging tips

- Use simple strings as opening/closing snippets while testing to verify matching behavior (the test-suite uses `RAW-COMPONENT-START` / `RAW-COMPONENT-END` for clarity).
- If attribute parsing behaves unexpectedly, create a small Blade string and call `Blade::render()` with `deleteCachedView: true` from tinker or a local test to reproduce the compiled result.

## Example — full end-to-end

1. Register component in a provider.
2. Render a Blade string using the tag.

```php
// registration in AppServiceProvider::boot
RawComponent::rawComponent('x-raw', '<b>', '</b>', '<b />');

// anywhere in code
echo Blade::render('<x-raw>Hello</x-raw>', deleteCachedView: true);
// => <b>Hello</b>\n
```
