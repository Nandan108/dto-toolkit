# Built-in Validation Attributes

This document lists all built-in validation attributes (`Validate`) provided by the DTO Toolkit.

---

## 🎯 About Validate Attributes

**Validators** enforce constraints on values. They never transform data; they throw
- a `GuardException` when input is invalid (user facing issue)
- an `InvalidConfigException` for dev-facing issues (e.g. incorrect parameters)

Typical use-cases:
- Guarding inbound values before casting
- Enforcing ranges, formats, or types
- Combining with casters/modifiers to build robust pipelines

Validators are repeatable, so you can stack multiple constraints on the same property.

---

## ✅ Available Validate Attributes

---

### Valid\NotNull

**Arguments:** _none_

Fails if the value is `null`.

**Example:**

```php
#[Valid\NotNull]
public ?string $name;
```

---

### Valid\NotBlank

**Arguments:** `bool $trim = true`

Fails if the value is an empty string (optionally trimmed).

**Example:**

```php
#[Valid\NotBlank(trim: true)]
public ?string $title;
```

---

### Valid\Length

**Arguments:** `?int $min = null, ?int $max = null`

Checks string length or array size is within bounds. Throws config error if both bounds are missing.

**Example:**

```php
#[Valid\Length(min: 3, max: 255)]
public ?string $username;
```

---

### Valid\Range

**Arguments:** `?float $min = null, ?float $max = null, bool $inclusive = true`

Ensures a number lies within the given range (inclusive by default).

**Example:**

```php
#[Valid\Range(min: 0, max: 1.0, inclusive: false)]
public ?float $ratio;
```

---

### Valid\Regex

**Arguments:** `string $pattern, bool $negate = false`

Passes if the value matches the regex (or does **not** match when `negate` is true). Validates the pattern at construction time.

**Example:**

```php
#[Valid\Regex('/^[A-Z0-9_]+$/')]
public ?string $slug;
```

---

### Valid\DateFormat

**Arguments:** `string $format`

Checks the value parses with `DateTimeImmutable::createFromFormat` using the given format.

**Example:**

```php
#[Valid\DateFormat('Y-m-d\TH:i:sP')]
public ?string $publishedAt;
```

---

### Valid\Email

**Arguments:** _none_

Validates an email address via `FILTER_VALIDATE_EMAIL`.

**Example:**

```php
#[Valid\Email]
public ?string $contactEmail;
```

---

### Valid\Url

**Arguments:** `string|array $scheme = ['http','https'], array $require = ['scheme','host']`

Validates URLs, enforcing allowed schemes and required parts (`scheme`, `host`, `path`, `query`).

**Example:**

```php
#[Valid\Url(require: ['scheme','host','path'])]
public ?string $website;
```

---

### Valid\Uuid

**Arguments:** _none_

Ensures the value matches a canonical v1–v5 UUID string.

**Example:**

```php
#[Valid\Uuid]
public ?string $id;
```

---

### Valid\InArray

**Arguments:** `array $choices, bool $strict = true`

Fails unless the value is one of the provided choices (strict comparison by default).

**Example:**

```php
#[Valid\InArray(['draft', 'published', 'archived'])]
public ?string $status;
```

---

### Valid\InstanceOfClass

**Arguments:** `class-string $className`

Requires the value to be an instance of the given class.

**Example:**

```php
#[Valid\InstanceOfClass(\DateTimeInterface::class)]
public ?\DateTimeInterface $endsAt;
```

---

### Valid\EnumCase

**Arguments:** `class-string<\BackedEnum> $enumClass`

Allows either an enum case instance of the given enum, or a value that maps to one via `tryFrom`.

**Example:**

```php
#[Valid\EnumCase(MyEnum::class)]
public MyEnum|string|int $status;
```

---

### Valid\EnumBackedValue

**Arguments:** `class-string<\BackedEnum> $enumClass`

Requires the **backing value** of the given enum (fails if an enum instance is provided).

**Example:**

```php
#[Valid\EnumBackedValue(MyEnum::class)]
public string|int $statusValue;
```

---

### Valid\IsArray

**Arguments:** _none_

Checks the value is an array.

**Example:**

```php
#[Valid\IsArray]
public ?array $items;
```

---

### Valid\IsInteger

**Arguments:** _none_

Accepts ints, float integers (e.g., `5.0`), or integer strings.

**Example:**

```php
#[Valid\IsInteger]
public int|float|string|null $count;
```

---

### Valid\IsFloat

**Arguments:** _none_

Accepts floats or float strings.

**Example:**

```php
#[Valid\IsFloat]
public float|string|null $price;
```

---

### Valid\IsNumeric

**Arguments:** _none_

Checks `is_numeric($value)`; accepts numeric strings too.

**Example:**

```php
#[Valid\IsNumeric]
public mixed $amount;
```

---

### Valid\IsNumericString

**Arguments:** _none_

Requires a string that is numeric (rejects non-strings even if numeric).

**Example:**

```php
#[Valid\IsNumericString]
public ?string $amountRaw;
```

