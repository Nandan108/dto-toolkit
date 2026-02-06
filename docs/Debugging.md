# 🔍 Debugging and Introspection

The DTO Toolkit provides several utilities and design affordances to help you trace, debug, and understand what happens during casting and normalization.

---

## 🧠 Inspecting Caster Memoization

Every time a `#[CastTo(...)]` attribute resolves to a callable, the system memoizes it in a shared internal cache.

To examine the contents:

```php
// Get all memoized casters
CastTo::_getNodeMetadata();

// Inspect a specific resolution path
CastTo::_getNodeMetadata('carbon');
```

Returns an object like:

```php
(object)[
    'caster' => Closure(...),
    'object' => "Nandan108\DtoToolkit\Laravel\Attribute\CastTo",
    'method' => "castToCarbon"
]
```

---

## 🧪 Enabling Debug Logging

You can manually hook in logs or dump calls for development:

```php
$caster = CastTo::_getNodeMetadata('model');
dump("Model caster resolved from: {$caster->object}::{$caster->method}");
```

You could also inject a logger or debugging hook later:

```php
CastTo::$onCastResolved = function ($casterMeta, $isCacheHit) {
    $state = ($isCacheHit ? 'already' : 'new');
    logger()->debug("DTO Toolkit: Cast using {$casterMeta[object]}::{$casterMeta[method]} ($state in cache)") ;
};
```

---

## 🧭 Processing Trace In Errors

When processing traces are disabled (by default in production),
`ProcessingException::getPropertyPath()` includes only the structural location
(properties and indices), with no processing-node information. For example:
```
prices[1]
```

When enabled, property paths include the processing chain / node path in braces,
for example:
```
prices{CastTo\Trimmed->CastTo\Split->Mod\PerItem}[1]{CastTo\Rounded->Assert\Range}
```

By default, trace inclusion follows dev mode (`ProcessingContext::isDevMode()`),
which is inferred from `APP_ENV`, `DEBUG`, and CLI usage. You can override this
behavior explicitly:


```php
// Disable traces (e.g. in production)
ProcessingContext::setIncludeProcessingTraceInErrors(false);

// Re-enable traces
ProcessingContext::setIncludeProcessingTraceInErrors(true);
```

---

## 🐞 Step Debugging with Xdebug

- Place a breakpoint inside `CastTo::getCaster()`
- You’ll be able to observe:
  - The resolution source (class/method/custom)
  - Constructor args and runtime args
  - The returned closure
- If using a framework adapter (e.g. Laravel), break within the adapter’s `resolve()` as well

---

## ❌ Diagnosing Failures

On failure to resolve a caster or validator, various exceptions may be thrown:
- `NodeProducerResolutionException`
- `InvalidConfigException`
- DI container (in a framework context)

This usually means:
- Typo in the `#[CastTo('xxx')]` value
- Missing `castToXxx()` method
- Invalid class name
- Class that doesn’t implement `CasterInterface` or `ValidatorInterface`
