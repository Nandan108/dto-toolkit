# Built-in Casting Attributes

This document lists all built-in attributes available in the DTO Toolkit, including value casters (`CastTo`) and chain modifiers (`CastModifier`).

---

## 🏷️ CastTo Attributes

These attributes perform direct value transformations.

### CastTo\ArrayFromCsv

**Arguments:** `string $separator = ',', bool $outbound = false`

Splits a CSV string into an array.
Throws a `CastingException` on input that can't be cast to string.

### CastTo\Boolean

Casts truthy/falsy values to boolean.
For string values, uses `filter_var($value, FILTER_VALIDATE_BOOLEAN)` ([see php doc](https://www.php.net/manual/en/function.filter-var.php))
Throws a `CastingException` on input that can't be cast to bool.

### CastTo\Capitalized

Capitalizes the first letter of a string.
Throws a `CastingException` on input that's not stringable'.

### CastTo\CsvFromArray

**Arguments:** `string $separator = '', bool $outbound = false`

Converts an array of strings into a comma-separated string.
Throws a `CastingException` on input that's not an array.

### CastTo\DateTime

**Arguments:** `string $format = 'Y-m-d H:i:s', bool $outbound = false`

Parses a datetime string into a `\DateTimeImmutable` instance.
Throws a `CastingException` if parsing fails (createFromFormat() returns false);

### CastTo\Enum

**Arguments:** `string $enumClass, bool $outbound = false`

Casts a scalar value to a PHP backed enum instance.
Throws an `InvalidArgumentException` if `$enumClass` doesn't exist or is not a backed enum.
Throws a `CastingException` at cast time if the value does not match any enum case.

### CastTo\Floating

Casts a numeric string or int to float.
Throws a `CastingException` on invalid input.

### CastTo\IfNull

**Arguments:** `mixed $fallback = false, bool $outbound = false`

Replaces `null` with the given default value.

### CastTo\Integer

**Arguments:** `IntCastMode $mode = IntCastMode::Trunc, bool $outbound = false`

Casts the value to an integer using a given `IntCastMode` strategy: `Ceil`, `Floor`, `Round`, or `Trunc`.

### CastTo\JsonEncode

**Arguments:** `int $flags = 0, int $depth = 512, bool $outbound = false`

Converts the value to a JSON string using `json_encode()`.
Throws a `CastingException` on failure.

### CastTo\Lowercase

Converts a string to lowercase.
Throws a `CastingException` on input that's not stringable'.

### CastTo\NullIf

**Arguments:** `mixed $when, bool $outbound = false`

Replaces a given value (or any of several) with `null`.

### CastTo\ReplaceIf

**Arguments:** `mixed $when, mixed $then = null, bool $outbound = false`

If the input equals (or is in) `$when`, replaces it with `$then`.

### CastTo\Rounded

**Arguments:** `int $precision = 0, bool $outbound = false`

Rounds a float rounded to specified precision.
Throws a CastingException on non-numeric input.

### CastTo\Slug

**Arguments:** `string $separator = '-', bool $outbound = false`

Converts a string into a URL-friendly slug.
⚠️ Requires php's Intl extension, which is used to strip diacritics and normalize special  latin characters into basic ones (Latin-ASCII). Throws a CastingException if the Transliterator doesn't exist (Intl extension not loaded).
Throws a CastingException on non-stringable input.

### CastTo\Str

Casts the value to a string using `(string)`.

### CastTo\Trimmed

**Arguments:** `string $characters = " \n\r\t\v\x00", string $where = 'both', bool $outbound = false`

Trims whitespace from both ends of a string.
Throws a CastingException on non-stringable input.

### CastTo\Uppercase

Converts a string to uppercase.
Throws a CastingException on non-stringable input.

---

## 🧩 CastModifier Attributes

These attributes affect the behavior of the casting chain itself.
### PerItem(N)
Expects `$value` to be an array, will throw a CastingException if it isn't.
Applies the next N caster(s) to each item of `$value` individually rather than to `$value` itself, as a whole.

### FailNextTo

**Arguments:** `mixed $fallback = null, string|array|null $handler = null, int $count = 1`

Wraps **the next N casters downstream** in a try/catch.

If one of the caster throws, `FailNextTo` will catch (CastingException $e), handle it and forward the resulting value downstream.
- if `$handler` is provided, takes the fallback value from `$handler($value, $fallback, $e, $dto)`
- if not, takes `$fallback` directly to feed it downstream.

### FailTo

**Arguments:** `mixed $fallback = null, string|array|null $handler = null, int $count = 1`

Wraps **all upstream casters**  in a try/catch.

If one of the caster throws, `FailTo` will catch (CastingException $e), handle it and forward the resulting value downstream.
- if `$handler` is provided, takes the fallback value from `$handler($value, $fallback, $e, $dto)`
- if not, takes `$fallback` directly to feed it downstream.
