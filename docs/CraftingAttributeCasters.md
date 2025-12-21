# 🧪 Crafting Custom Attribute Casters

This guide explains how to create your own `Cast\To*` casters in DTO Toolkit. You’ll learn how to:

- Create a minimal caster class
- Use `BootsOnDtoInterface` for context-sensitive setup
- Handle flexible parameters with `UsesParamResolver`
- Validate input and throw proper `CastingException`s

> ⚠️ Caster instances are memoized per set of `$constructorArgs` — not per DTO.
> This means you **cannot safely use instance properties to hold mutable state**.
> If you want DTO-specific config, use `bootOnDto()` with `UsesParamResolver` and pass needed values via `constructorArgs`.

---

## ✅ When Should You Write a Custom Caster?

Create a caster when you need to:

- Normalize input types (e.g. `string|int` to `DateTime`)
- Apply consistent formatting or cleaning (e.g. `trim()`, remove diacritics)
- Convert between localized or unit-specific formats (e.g. `1.234,56 CHF` → float)
- Integrate with external logic or services (e.g. slug generation)

---

## 🧱 Minimal Caster Structure

To write a custom caster, implement `CasterInterface` and :
- Extend `CastBase` if it expects arguments, and pass them to `parent::__construct()` in the constructor.
- Extend `CastBaseNoArgs` otherwise (no need for a constructor).

```php
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Contracts\CasterInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class MyCustomCaster extends CastBase implements CasterInterface
{
    public function __construct($foo, $bar, $baz = null) {
        parent::__construct(
            // will be passed to each call to cast($value, $args)
            args: [$foo, $bar],
            // will be available at $this->constructorArgs
            // will be used to construct instance-cache key
            constructorArgs: ['baz' => $baz]
        )
    }

    public function cast(mixed $value, array $args): mixed
    {
        // transform $value and return it
        // You can access:
        // - [$foo, $bar] = $args;
        // - ['baz' => $baz] = $this->constructorArgs;
        // - static::getCurrentDto() (the DTO instance)
        // - static::getCurrentPropName() (name of the property being cast)
    }
}
```

---

## 🚀 Using `BootsOnDtoInterface`

If your caster needs per-DTO setup (e.g., to precompute values or access config), implement:

```php
use Nandan108\DtoToolkit\Contracts\BootsOnDtoInterface;

class MyCaster extends CastBaseNoArgs implements CasterInterface, BootsOnDtoInterface
{
    public function bootOnDto(): void
    {
        $dto = static::$currentDto;
        // Prepare anything context-specific here
    }
}
```

> Same caster instance may be reused across DTOs — always isolate DTO-scoped state.

---

## 🧩 Dynamic Parameter Resolution with `UsesParamResolver`

Some casters need params like `locale`, `unit`, or `timezone` that should come from different sources:

- Attribute value (`#[CastTo\Foo(locale: 'fr_CH')]`)
- DTO method (`$dto->getLocale()`)
- DTO context (`$dto->getContext('locale')`)
- Static provider class (`LocaleProvider::getLocale(...)`)
- Fallback closure (e.g., `locale_get_default()`)

The `UsesParamResolver` trait simplifies this.

### 🔧 Setup in Constructor + `bootOnDto()`

```php
public function __construct(?string $locale = null)
{
    $this->ensureExtensionLoaded('intl');
    parent::__construct(args: [], constructorArgs: ['locale' => $locale]);
}

public function bootOnDto(): void
{
    $this->configureParamResolver(
        dto: static::$currentDto,
        paramName: 'locale',
        valueOrProvider: $this->constructorArgs['locale'],
        checkValid: fn ($v) => is_string($v) && strlen($v) >= 2,
        fallback: fn () => locale_get_default()
    );
}
```

### 🔍 Then resolve it inside `cast()`

```php
$locale = $this->resolveParam('locale', $value);
```

---

## 🎯 Use `UsesLocaleResolver` or `UsesTimeZoneResolver`

Shortcut traits for locale and timezone resolution are built-in to DTO Core:

```php
use Nandan108\DtoToolkit\Traits\UsesTimeZoneResolver;

class MyCaster extends CastBase implements CasterInterface, BootsOnDtoInterface
{
    use UsesTimeZoneResolver;

    public function __construct(?string $timezone = null)
    {
        parent::__construct(constructorArgs: ['timezone' => $timezone]);
    }

    public function bootOnDto(): void
    {
        $this->configureTimezoneResolver();
    }

    public function cast(mixed $value, array $args): mixed
    {
        $tz = $this->resolveParam('timezone', $value);
        // use $tz to cast
    }
}
```

---

## 📊 Resolution Priority (for valueOrProvider)

| Example for `$locale` argument               | Resolved from                                                  |
|--------------------|-----------------------------------------------------------------|
| `Provider::class`  | `Provider::getXyz($value, $prop, $dto)`                   |
| `'<dto'`           | `$dto->getXyz($value, $prop)`                                          |
| `'<dto:aMethod'`   | `$dto->aMethod($value, $prop)`                                         |
| `'<dto:aMethod:{"foo":[1,2]}'`<br>*json-formatted extra parameter* | `$dto->aMethod($value, $prop, ['foo' => [1, 2]])`                                         |
| `'<context'`       | `$dto->getContext($paramName)`                                 |
| `'<context:key'`       | `$dto->getContext('key')`                                 |
| `'<context:key=val'`   | `$dto->getContext('key') === 'val'` (returns a bool)                    |
| `'<context:key=/regexp/i'`   | `preg_match('/regexp/i', $dto->getContext('key'))` (returns a bool)                    |
| `'fr_CH'` (string) | Used directly if value is deemed valid by that particular resolver (timezone, locale or other) |
| `null`                 | Fallback order:<br>1. `$dto->getContext($paramName)` (if context key exists)<br>2. `$dto->{"get$ParamName"}` (if method exists)<br>3. resolver's `fallback($value, $dto)`   |

---

## ❌ Validating and Failing Gracefully

Always validate input early. Use:

```php
if (!is_numeric($value)) {
    throw TransformException::expected(static::class, $value, 'numeric');
}
```

Shortcuts:

```php
$value = $this->ensureStringable($value);
$value = $this->throwIfNotNumeric($value);
```

---

## 🧪 Best Practices

- ✅ Extend `CastBase` — gives you consistent lifecycle and context access
- ✅ Use `constructorArgs` to control caching and boot lifecycle
- ✅ Use `bootOnDto()` for context-sensitive setup
- ✅ Use `UsesParamResolver` (or derived traits) for flexible config
- ✅ Keep `cast()` clean and focused — resolve all params first

---

## 📚 Examples in the Toolkit

- `CastTo\localizedDateTime` — resolves locale and timezone dynamically, supports custom formats
- `CastTo\Join` — takes a `$delimiter` param and implodes `array $value`
- `CastTo\JsonExtract` — takes dot-notation `$path` parameter, extracts a single value at `$path` from nested structure `$value`

For additional help with dependency injection or service-based casters, see [Dependency Injection](DI.md).
