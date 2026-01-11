# Built-in Validation Attributes

This document lists all built-in validation attributes (`Assert`) provided by the DTO Toolkit.

---

## 🎯 About Assert Attributes

**Validators** enforce constraints on values. They never transform data; they throw
- a `GuardException` when input is invalid (user facing issue)
- an `InvalidConfigException` for dev-facing issues (e.g. incorrect parameters)

Typical use-cases:
- Guarding inbound values before casting
- Enforcing ranges, formats, or types
- Combining with casters/modifiers to build robust pipelines

Validators are repeatable, so you can stack multiple constraints on the same property.

---

## ✅ Available Assert Attributes

---

### Assert\IsNull

**Arguments:** `bool $expect = true`

Passes when the value is `null` (or fails when `$expect` is `false`).

**Example:**

```php
#[Assert\IsNull(false)]
public ?string $name;
```

---

### Assert\IsBlank

**Arguments:** `bool $expect = true`

Blank values include: `null`, empty string, whitespace-only strings, empty arrays, and empty iterables. `0`, `'0'`, and `false` are **not** blank.

**Example:**

```php
#[Assert\IsBlank(false)]
public ?string $title;
```

---

### Assert\CompareTo

**Arguments:** `string $op, mixed $scalar`

Compares the value against a scalar using a comparison operator.

**Example:**

```php
#[Assert\CompareTo('>=', 18)]
public int $age;
```

---

### Assert\CompareToExtract

**Arguments:** `string $op, string $rightPath, ?string $leftPath = null`

Compares a value (or extracted left path) against a value extracted from the DTO/context.

**Example:**

```php
#[Assert\CompareToExtract('==', '$context.expectedRole')]
public string $role;
```

---

### Assert\Equals

**Arguments:** `mixed $value, bool $strict = true`

Checks the value equals the expected value (strict by default).

**Example:**

```php
#[Assert\Equals('draft', strict: true)]
public string $status;
```

---

### Assert\Length

**Arguments:** `?int $min = null, ?int $max = null`

Checks string length or array size is within bounds. Throws config error if both bounds are missing.

**Example:**

```php
#[Assert\Length(min: 3, max: 255)]
public ?string $username;
```

---

### Assert\Range

**Arguments:** `?float $min = null, ?float $max = null, bool $inclusive = true`

Ensures a number lies within the given range (inclusive by default).

**Example:**

```php
#[Assert\Range(min: 0, max: 1.0, inclusive: false)]
public ?float $ratio;
```

---

### Assert\Regex

**Arguments:** `string $pattern, bool $negate = false`

Passes if the value matches the regex (or does **not** match when `negate` is true). Validates the pattern at construction time.

**Example:**

```php
#[Assert\Regex('/^[A-Z0-9_]+$/')]
public ?string $slug;
```

---

### Assert\DateFormat

**Arguments:** `string $format`

Checks the value parses with `DateTimeImmutable::createFromFormat` using the given format.

**Example:**

```php
#[Assert\DateFormat('Y-m-d\TH:i:sP')]
public ?string $publishedAt;
```

---

### Assert\Email

**Arguments:** _none_

Validates an email address via `FILTER_VALIDATE_EMAIL`.

**Example:**

```php
#[Assert\Email]
public ?string $contactEmail;
```

---

### Assert\Url

**Arguments:** `string|array $scheme = ['http','https'], array $require = ['scheme','host']`

Validates URLs, enforcing allowed schemes and required parts (`scheme`, `host`, `path`, `query`).

**Example:**

```php
#[Assert\Url(require: ['scheme','host','path'])]
public ?string $website;
```

---

### Assert\Uuid

**Arguments:** _none_

Ensures the value matches a canonical v1–v5 UUID string.

**Example:**

```php
#[Assert\Uuid]
public ?string $id;
```

---

### Assert\In

**Arguments:** `array $choices, bool $strict = true`

Fails unless the value is one of the provided choices (strict comparison by default).

**Example:**

```php
#[Assert\In(['draft', 'published', 'archived'])]
public ?string $status;
```

---

### Assert\IsInstanceOf

**Arguments:** `class-string $className`

Requires the value to be an instance of the given class or interface.

**Example:**

```php
#[Assert\InstanceOf(\DateTimeInterface::class)]
public ?\DateTimeInterface $endsAt;
```

---

### Assert\EnumCase

**Arguments:** `class-string<\BackedEnum> $enumClass`

Allows either an enum case instance of the given enum, or a value that maps to one via `tryFrom`.

**Example:**

```php
#[Assert\EnumCase(MyEnum::class)]
public MyEnum|string|int $status;
```

---

### Assert\EnumBackedValue

**Arguments:** `class-string<\BackedEnum> $enumClass`

Requires the **backing value** of the given enum (fails if an enum instance is provided).

**Example:**

```php
#[Assert\EnumBackedValue(MyEnum::class)]
public string|int $statusValue;
```

---

### Assert\IsType

**Arguments:** `string|array $type`

Checks the value matches one of the supported types (string or list). Supports: `bool`, `int`, `float`, `numeric`, `string`, `class-string`, `scalar`, `array`, `iterable`, `countable`, `callable`, `object`, `resource`, `null` (plus common aliases).

**Example:**

```php
#[Assert\IsType(['int', 'float'])]
public int|float $amount;
```

---

### Assert\ContainedIn

**Arguments:** `string|array|iterable $haystack, null|"start"|"end"|int $at`

Checks whether the **value** appears as a contiguous subsequence of the haystack.
The value and haystack must both be strings or both be iterables.
When `$at` is:
- `null`: match anywhere
- `"start"` / `"end"`: anchored match
- `int`: absolute start index; negative values are end-relative (e.g. `-1` means
  the match ends 1 element before the end); `0` behaves like `"start"`.

Negative `$at` requires a countable iterable and throws an `InvalidConfigException` otherwise.

**Example:**

```php
#[Assert\ContainedIn(['a', 'b', 'c'])]
public array $letters;
```

---

### Assert\Contains

**Arguments:** `string|array|iterable $needle, null|"start"|"end"|int $at`

Checks whether the **value** contains the needle as a contiguous subsequence.
The value and needle must both be strings or both be iterables.
When `$at` is:
- `null`: match anywhere
- `"start"` / `"end"`: anchored match
- `int`: absolute start index; negative values are end-relative (e.g. `-1` means
  the match ends 1 element before the end); `0` behaves like `"start"`.

Negative `$at` requires a countable iterable and throws an `InvalidConfigException` otherwise.

**Example:**

```php
#[Assert\Contains('foo')]
public string $title;
```

---

### Assert\IsNumericString

**Arguments:** _none_

Requires a string that is numeric (rejects non-strings even if numeric).

**Example:**

```php
#[Assert\IsNumericString]
public ?string $amountRaw;
```
