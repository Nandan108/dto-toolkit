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

Regardless of trace mode, `ProcessingException::getThrowerNodeName()` returns the
name of the node that threw (or rethrew) the exception. This is useful in
production mode where `getPropertyPath()` intentionally omits chain provenance.

### Node-name contract

To avoid leaking internal class details in user-facing messages:

- Trace node names and thrower node names use processing node names, not fully
  qualified implementation class names.
- Anonymous class internals are not exposed in public error metadata.
- DTO method-based nodes use the DTO processing node name (default: `DTO`),
  then the method, for example: `num{DTO::assertFoo}`.
- Custom producers can override the displayed node name by implementing
  `ProvidesProcessingNodeNameInterface`.

This contract applies to both:
- `ProcessingException::getPropertyPath()` (when traces are enabled)
- `ProcessingException::getThrowerNodeName()` (always available)

By default, trace inclusion follows dev mode (`ProcessingContext::isDevMode()`), which is inferred from `APP_ENV`, `DEBUG`, and CLI usage. You can override this behavior explicitly:


```php
// Disable traces
ProcessingContext::setIncludeProcessingTraceInErrors(false);
```
Please note:
- This setting is global, and in normal operation should only be set once, at boot time.
- This setting can only be changed while no processing is taking place.
- Processing chains are JIT compiled and cached, therefore to ensure coherent and
  consistent behavior, changing this setting will clear all caches (`BaseDto::clearAllCaches()` will be called)
- The above rules apply to both setIncludeProcessingTraceInErrors() and setDevMode()

This setting is normally meant to be used only once, at boot time, if at all.

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
