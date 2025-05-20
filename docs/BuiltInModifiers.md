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

---

### Mod\ApplyNextIf

**Arguments:** `mixed $condition, int $count = 1, bool $negate = false`

Conditionally applies the next `$count` casters if the given `$condition` is true (or false if `negate` is true). The condition can be a value, a context key, or a method reference.

**Example:**

```php
#[Mod\ApplyNextIf('<dto:shouldCast', 2)]
#[CastTo\Trimmed]
#[CastTo\Uppercase]
public ?string $name;
```

---

### Mod\Collect

**Arguments:** `array|int $countOrKeys`

Runs input through multiple parallel subchains and collects their outputs.

If keys are provided, returns an associative array.

**Example:**

```php
#[Collect(keys: ['original', 'pascal', 'kebab'])]
#[Mod\NoOp]
#[CastTo\PascalCase]
#[CastTo\KebabCase]
public ?array $identifier;
```

---

### Mod\FailIf

**Arguments:** `mixed $condition, bool $negate = false`

Throws a casting exception if the `$condition` is true (or false if `negate` is true). Can be used as a simple validator.

**Example:**

```php
#[Mod\FailIf('<dto:isInvalid')]
public ?string $field;
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

### Mod\FailNextTo

**Arguments:** `mixed $fallback = null, string|array|null $handler = null, int $count = -1`

Wraps the following casters in a try/catch block.
- If `$count` < 0 (default), `FailNextTo` will wrap as many casters as are available after it.
- If `$count` > 0, `FailNextTo` will wrap extactly `$count` casters (or chain nodes) following it, and throw if less are available.
- If f `$count` = 0, throws an InvalidArgumentException.

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

### Mod\FirstSuccess

**Arguments:** `int $count`

Tries the next `$count` casters/subchains in order and returns the result of the first one that succeeds. Throws if all fail.

**Example:**

```php
#[Mod\FirstSuccess(3)]
#[CastTo\DateTimeFromLocalized(pattern: 'yyyy-MM-dd')]
#[CastTo\DateTimeFromLocalized(dateStyle: \IntlDateFormatter::SHORT)]
#[CastTo\DateTimeFromLocalized(dateStyle: \IntlDateFormatter::LONG)]
public null|string|DateTimeInterface $date;
```

---

### Mod\Groups

**Arguments:** `string|array $groups, int $count = 1`

Activates or skips the wrapped node(s) based on active context groups.

In the following example, the `Trimmed` caster will only be applied if the group 'api' is "in scope". E.g. `MyDTO::withGroups('api')->fromArray(...)`

**Example:**

```php
#[Groups('api'), CastTo\Trimmed]
#[CastTo\Slug]
public ?string $slug;
```

---

### Mod\Wrap

**Arguments:** `int $count`

Groups the next `$count` casters into a single subchain treated as one unit.

Useful when modifiers (like FirstSuccess) expect grouped casters.

**Example:**

```php
#[FirstSuccess(2)]
#[Wrap(3), Valid\Integer, CastTo\Integer, Valid\Range(0, 100)]
#[Wrap(2), CastTo\Floating, Valid\Range(0, 1)]
null|int|float $percent;
```

---

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

### Mod\NoOp

**Arguments:** _none_

A no-op modifier. Equivalent to `Wrap(0)`. Useful for conditional chains or as a placeholder.

**Example:**

```php
#[Mod\NoOp]
public mixed $value;
```
---

### Mod\SkipNextIf

**Arguments:** `mixed $condition, int $count = 1`

Skips the next `$count` casters if the given `$condition` is true. Syntactic sugar for `ApplyNextIf(..., negate: true)`.

**Example:**

```php
#[Mod\SkipNextIf('<context:isAdmin')]
#[CastTo\Trimmed]
public ?string $comment;
```

---

### Mod\Wrap

**Arguments:** `int $count`

Groups the next `$count` casters into a subchain. Does not alter behavior, but is useful for grouping with other modifiers.

**Example:**

```php
#[Mod\Wrap(2)]
#[CastTo\Trimmed]
#[CastTo\Uppercase]
public ?string $name;
```

---

## 🧠 Reminder

- Modifiers are phase-aware: inbound vs outbound
- Modifiers compose naturally with casters
- Modifiers are lightweight and declarative: they express "how" and "when" processing happens, not "what" the value becomes
