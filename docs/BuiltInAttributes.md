# Built-in Casting Attributes

This document lists all built-in attributes available in the DTO Toolkit, including value casters (`CastTo`) and chain modifiers (`CastModifier`).

---

## 🏷️ CastTo Attributes

These attributes perform direct value transformations.

### CastTo\Split

**Arguments:** `string $separator = ','`

Splits a CSV string into an array.
Throws a `CastingException` on input that can't be cast to string.

### CastTo\Boolean

Casts truthy/falsy values to boolean.
For string values, uses `filter_var($value, FILTER_VALIDATE_BOOLEAN)` ([see php doc](https://www.php.net/manual/en/function.filter-var.php))
Throws a `CastingException` on input that can't be cast to bool.

### CastTo\Capitalized

Capitalizes the first letter of a string.
Throws a `CastingException` on input that's not stringable'.

### CastTo\Join

**Arguments:** `string $separator = ''`

Converts an array of strings into a comma-separated string.
Throws a `CastingException` on input that's not an array.

### CastTo\DateTime

**Arguments:** `string $format = 'Y-m-d H:i:s'

Parses a datetime string into a `\DateTimeImmutable` instance.
Throws a `CastingException` if parsing fails (createFromFormat() returns false);

### CastTo\Enum

**Arguments:** `string $enumClass`

Casts a scalar value to a PHP backed enum instance.
Throws an `InvalidArgumentException` if `$enumClass` doesn't exist or is not a backed enum.
Throws a `CastingException` at cast time if the value does not match any enum case.

### CastTo\Floating

Casts a numeric string or int to float.
Throws a `CastingException` on invalid input.

### CastTo\IfNull

**Arguments:** `mixed $fallback = false`

Replaces `null` with the given default value.

### CastTo\Integer

**Arguments:** `IntCastMode $mode = IntCastMode::Trunc`

Casts the value to an integer using a given `IntCastMode` strategy: `Ceil`, `Floor`, `Round`, or `Trunc`.

### CastTo\JsonEncode

**Arguments:** `int $flags = 0, int $depth = 512`

Converts the value to a JSON string using `json_encode()`.
Throws a `CastingException` on failure.

### CastTo\Lowercase

Converts a string to lowercase.
Throws a `CastingException` on input that's not stringable'.

### CastTo\NullIf

**Arguments:** `mixed $when

Replaces a given value (or any of several) with `null`.

### CastTo\ReplaceIf

**Arguments:** `mixed $when, mixed $then = null`

If the input equals (or is in) `$when`, replaces it with `$then`.

### CastTo\Rounded

**Arguments:** `int $precision = 0`

Rounds a float rounded to specified precision.
Throws a CastingException on non-numeric input.

### CastTo\Slug

**Arguments:** `string $separator = '-'`

Converts a string into a URL-friendly slug.
⚠️ Requires php's Intl extension, which is used to strip diacritics and normalize special  latin characters into basic ones (Latin-ASCII). Throws a CastingException if the Transliterator doesn't exist (Intl extension not loaded).
Throws a CastingException on non-stringable input.

### CastTo\Str

Casts the value to a string using `(string)`.

### CastTo\Trimmed

**Arguments:** `string $characters = " \n\r\t\v\x00", string $where = 'both'`

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

---

# Built-in Modifiers (flow control)

**Usage**:
```php
use Nandan108\DtoToolkit\Attribute\CastModifier as Mod;

class MyDto extends FullDto {
    ...
    #[Mod\Modifier(...args)]
    ...
    public sometype|null $myProp;
}

```

| Modifier | Purpose | Behavior |
|-|-|-|
| `PerItem($count=1)` | Apply next `$count` casters or subchains to each item of an input array | Casts each array element individually |
| `FailNextTo( $fallback=null, $handler=null, $count=1)` | Handle failure from a subchain made of the next `$count` casters | Wraps the next `$count` casters as one subchain, If if a casting failure happens within that subchain, fallback gracefully by returning the return value of callback `handler($value, $fallback, $e, $dto)` if provided, or returning `$fallback` if not  |
| `FailTo( $fallback=null, $handler=null)` | Handle failure of entire upstream chain | If any upstream caster fails, return fallback value |
| `Groups(group\|[groups])` | Conditionally activate subchains based on context groups | Skips subchain if active groups don't match |
| *`Wrap($count)`* * | Group `$count` casters together into a parenthesized subchain | Treats them as one atomic operation |
| *`Collect(N, keys=[])`* * | Run input through `$count` parallel subchains | Returns array of results from each branch. If provided with `$count` keys, returns an associative array. |
| *`SkipIfMatch([values], return val)`* * | Short-circuit chain if input matches certain values | Immediately returns $return if matched |

\* *comming soon*