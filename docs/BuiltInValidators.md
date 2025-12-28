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

### Assert\NotNull

**Arguments:** _none_

Fails if the value is `null`.

**Example:**

```php
#[Assert\NotNull]
public ?string $name;
```

---

### Assert\NotBlank

**Arguments:** `bool $trim = true`

Fails if the value is an empty string (optionally trimmed).

**Example:**

```php
#[Assert\NotBlank(trim: true)]
public ?string $title;
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

### Assert\InArray

**Arguments:** `array $choices, bool $strict = true`

Fails unless the value is one of the provided choices (strict comparison by default).

**Example:**

```php
#[Assert\InArray(['draft', 'published', 'archived'])]
public ?string $status;
```

---

### Assert\InstanceOfClass

**Arguments:** `class-string $className`

Requires the value to be an instance of the given class.

**Example:**

```php
#[Assert\InstanceOfClass(\DateTimeInterface::class)]
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

### Assert\IsArray

**Arguments:** _none_

Checks the value is an array.

**Example:**

```php
#[Assert\IsArray]
public ?array $items;
```

---

### Assert\IsInteger

**Arguments:** _none_

Accepts ints, float integers (e.g., `5.0`), or integer strings.

**Example:**

```php
#[Assert\IsInteger]
public int|float|string|null $count;
```

---

### Assert\IsFloat

**Arguments:** _none_

Accepts floats or float strings.

**Example:**

```php
#[Assert\IsFloat]
public float|string|null $price;
```

---

### Assert\IsNumeric

**Arguments:** _none_

Checks `is_numeric($value)`; accepts numeric strings too.

**Example:**

```php
#[Assert\IsNumeric]
public mixed $amount;
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

