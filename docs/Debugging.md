# 🔍 Debugging and Introspection

The DTO Toolkit provides several utilities and design affordances to help you trace, debug, and understand what happens during casting and normalization.

---

## 🧠 Inspecting Caster Memoization

Every time a `#[CastTo(...)]` attribute resolves to a callable, the system memoizes it in a shared internal cache.

To examine the contents:

```php
// Get all memoized casters
CastTo::getCasterMetadata();

// Inspect a specific resolution path
CastTo::getCasterMetadata('carbon');
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
$caster = CastTo::getCasterMetadata('model');
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

## 🐞 Step Debugging with Xdebug

- Place a breakpoint inside `CastTo::getCaster()`
- You’ll be able to observe:
  - The resolution source (class/method/custom)
  - Constructor args and runtime args
  - The returned closure
- If using a framework adapter (e.g. Laravel), break within the adapter’s `resolve()` as well

---

## ❌ Diagnosing Failures

If a caster cannot be resolved, you’ll get a `CastingException` like:

```
CastingException: Caster 'unknownCaster' could not be resolved.
```

This usually means:
- A typo in the `#[CastTo('xxx')]` value
- A missing `castToXxx()` method
- A class that doesn’t implement `CasterInterface`
---

## 🧼 Summary

- Use `getCasterMetadata()` to inspect or test resolution
- Use `CastingException` for resolution failure handling
- Collision works out of the box for better exception handling in CLI or test runs
