# Built-in Casting Modifiers

This document lists all built-in chain modifiers (`ChainModifier`) available in the DTO Toolkit.

---

## 🎯 About ChainModifier Attributes

**Modifiers** alter how the processing chain behaves without transforming the value directly.

They typically:
- Group multiple casters together
- Apply conditions, error handling, or branching logic
- Control when and how certain transformations are applied

You can think of a modifier as a *wrapper* that "parenthesizes" part of the processing chain:
similar to how in math you would group operations like `f(g(x, y, z))`.

Modifiers allow building powerful, fine-grained, and safe transformation chains.

---

## 🧩 Available ChainModifier Attributes

### Mod\PerItem

**Arguments:** `int $count = 1`

Applies the next `$count` casters (or wrapped subchains) individually to **each item** in an array.

**Example:**

```php
#[CastTo\Split]
#[Mod\PerItem(1)]
#[CastTo\Integer]
public ?array $ages;
```

---

### Mod\FailNextTo

**Arguments:** `mixed $fallback = null, string|array|null $handler = null, int $count = 1`

Wraps the next `$count` casters in a try/catch block.

If casting fails in this subchain:
- If `$handler` is provided, calls `$handler($value, $fallback, $exception, $dto)`
- Otherwise returns `$fallback`

**Example:**

```php
#[FailNextTo('N/A')]
#[CastTo\Slug]
public ?string $slug;
```

---

### Mod\FailTo

**Arguments:** `mixed $fallback = null, string|array|null $handler = null`

Wraps **all upstream casters** in a try/catch.

Catches any exception from previous casters and gracefully recovers.

**Example:**

```php
#[CastTo\Trimmed]
#[CastTo\Slug]
#[FailTo('invalid')]
public ?string $slug;
```

---

### Mod\Groups

**Arguments:** `string|array $groups`

Activates or skips the next chain segment based on active context groups.

**Example:**

```php
#[Groups('api')]
#[CastTo\Trimmed]
#[CastTo\Slug]
public ?string $slug;
```

---

### Mod\Wrap

**Arguments:** `int $count`

Groups the next `$count` casters into a single subchain treated as one unit.

Useful when modifiers (like PerItem) expect grouped casters.

**Example:**

```php
#[Wrap(2)]
#[CastTo\Integer]
#[Valid\Range(0, 99)]
```

---

### Mod\Collect

**Arguments:** `int $count, array $keys = []`

Runs input through multiple parallel subchains and collects their outputs.

If keys are provided, returns an associative array.

**Example:**

```php
#[Collect(2, keys: ['min', 'max'])]
#[CastTo\Integer]
#[CastTo\Integer]
public ?array $bounds;
```

---

### Mod\SkipIfMatch

**Arguments:** `array $values, mixed $return`

Short-circuits the chain if the input matches one of the provided values.

**Example:**

```php
#[SkipIfMatch(['', null], return: 'default')]
public ?string $description;
```

---

## 🧠 Reminder

- Modifiers are phase-aware: inbound vs outbound
- Modifiers compose naturally with casters
- Modifiers are lightweight and declarative: they express "how" and "when" processing happens, not "what" the value becomes
