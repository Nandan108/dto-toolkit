# Built-in Caster Attributes

This document lists all built-in value transformation attributes (`CastTo`) provided by the DTO Toolkit.

> Note: Framework-specific casters will be included in the corresponding framwork adapters

---

## 🎯 About CastTo Attributes

**CastTo attributes** transform individual property values during the DTO's lifecycle.

They are typically applied when:
- Normalizing inbound data (e.g., from a request or form)
- Preparing outbound data (e.g., for API responses or database persistence)

Each caster:
- Accepts and processes a **single value**
- Either **transforms** or **sanitizes** the value
- Throws a `TransformException` if the input is invalid or can't be transformed

These attributes can be freely combined and chained to compose complex transformations.

---

## 🏷️ Available CastTo Attributes

> 💡 Parameters like `?string $locale = null` or `?string $timezone = null` support flexible resolution.
> See [Parameter Resolution](#parameter-resolution) below for details.

### Case converters

**Arguments:** _none_
All throw a `TransformException` if the input is not stringable.

#### Standard lower/upper case

- CastTo\**Lowercase**: `"postalCode"` ➔ `"postalcode"`
- CastTo\**Uppercase**: `"postalCode"` ➔ `"POSTALCODE"`

#### Identifier Casing Styles

Words in input value may be separated by non-letter characters ***or*** a change of case.

- CastTo\**CamelCase**:  `"postal_code"` ➔ `"postalCode"`
- CastTo\**PascalCase**:  `"postal_code"` ➔ `"PostalCode"`
- CastTo\**KebabCase**:  `"postalCode"` ➔ `"postal-code"`
- CastTo\**SnakeCase**:  `"PostalCode"` ➔ `"postal_code"`
- CastTo\**UpperSnakeCase**:  `"PostalCode"` ➔ `"POSTAL_CODE"`

---

### CastTo\Base64

**Arguments:** *none*

Encodes a string into Base64 format.
Throws a `TransformException` if the input is not stringable.

---

### CastTo\FromBase64

**Arguments:** `bool $strict = false`

Decodes a Base64-encoded string.
If `$strict` is true, decoding will fail on invalid characters.
Throws a `TransformException` if the input is not a valid Base64 string or if decoding fails.

---

### CastTo\Boolean

Casts truthy/falsy values to boolean.
For string inputs, uses `filter_var($value, FILTER_VALIDATE_BOOLEAN)`.
Throws a `TransformException` on invalid input.

---

### CastTo\Capitalized

Capitalizes the first letter of a string.
Throws a `TransformException` if the input is not stringable.

---

### CastTo\DateTime

**Arguments:**
- `string $format = 'Y-m-d H:i:s'`
- `?string $timezone = null` ([see resolution](#parameter-resolution))

Parses a datetime string into a `\DateTimeImmutable` instance.
Optionally coerces the result to a given timezone.
Throws a `TransformException` if parsing fails.

---

### CastTo\DateTimeFromLocalized

**Arguments:**
- `?string $locale = null,` ([see resolution](#parameter-resolution))
- `int $dateStyle = \IntlDateFormatter::SHORT,`
- `int $timeStyle = \IntlDateFormatter::SHORT,`
- `?string $pattern = null,`
- `?string $timezone = null,` ([see resolution](#parameter-resolution))



Parses a locale-dependent date/time string using PHP's `IntlDateFormatter`.
Supports fallback resolution for locale and timezone.
Throws a `TransformException` on invalid input.

---

### CastTo\DateTimeString

**Arguments:**
- `string $format = 'Y-m-d H:i:s'`

Converts a `DateTimeInterface` object to a formatted string.
Throws a `TransformException` if the input is not a `DateTimeInterface`.

---

### CastTo\Dto

**Arguments:**
- `class-string<BaseDto&CreatesFromArrayOrEntityInterface> $dtoClass`

Converts an array or object into a nested DTO instance using `newFromArray()` / `newFromEntity()`.
Throws a `TransformException` if the input is not an array or object.

**Example:**

```php
#[CastTo\Dto(AddressDto::class)]
public AddressDto | array | null $address = null;
```

---

### CastTo\Entity

**Arguments:**
- `?class-string $entityClass = null`
- `bool $recursive = false`

Exports a DTO or array into an entity instance.

- When the source is a DTO, outbound processing is applied before export.
- When the source is an array, values are used as-is.

If `$entityClass` is omitted, the target entity is resolved in this order:
1. A `#[DefaultOutboundEntity]` attribute on the DTO (matching the current scope)
2. [PreparesEntityInterface](Attributes.md#PreparesEntityInterface) implemented by the DTO (`prepareEntity()` method)
3. Otherwise, an `InvalidConfigException` is thrown

When `$recursive` is true, nested DTOs encountered during export are also converted to entities using the same execution context. This means that all nested DTOs must be exportable; otherwise a configuration error is raised. For partial exports, use explicit casting on individual properties.


**Example:**

```php
#[CastTo\Entity(UserEntity::class, recursive: true)]
public UserEntity | array | null $user = null;
```

---

### CastTo\AsArray

**Arguments:**
- `array $supplementalProps = []`
- `bool $recursive = false`

Exports a DTO or other object into an array.

- When the source is a DTO, outbound processing is applied before export.
- When the source is a plain object, public properties/accessors are read directly.
- When the source is `\Traversable`, it is materialized with keys preserved.

Supplemental props are merged into the output. On key collisions, source values take precedence.

When `$recursive` is true, nested DTOs encountered during export are converted to arrays using the same execution context.

This caster is useful when fine-grained, property-level control is needed, as an alternative to calling `exportToArray(recursive: true)` imperatively.

**Example:**

```php
#[CastTo\AsArray(['role' => 'admin'], recursive: true)]
public array $user_export = [];
```


---

### CastTo\Age

**Arguments:**
- `string $in = 'years'` (`'seconds'|'hours'|'days'|'years'`)
- `?string $relativeTo = null` (ISO datetime string, timezone allowed)

Computes the age/difference between the input ISO datetime string and `$relativeTo`.
If `$relativeTo` is `null`, the current time in UTC is used.
Negative values are allowed for future dates.
Years are computed as 365 days.

---

### CastTo\Enum

**Arguments:** `string $enumClass`

Casts a scalar value to a PHP backed enum.
Throws an `InvalidArgumentException` if `$enumClass` is invalid.
Throws a `TransformException` if the value does not match any enum case.

---

### CastTo\Floating

**Arguments:**
- `?string $decimalPoint = null`

Normalizes and casts numeric-looking strings to `float`.

If a decimal point character is provided (e.g., `','`), the string will be cleaned by keeping only digits, one decimal point, and at most one, leading minus sign.

Examples:
- `'1 234,56'` with `','` ➔ `1234.56`
- `'-1_000.50'` ➔ `-1000.5`

Throws a `TransformException` if the input is not stringable or cannot be interpreted as numeric.

---

### CastTo\FromJson

**Arguments:** *none*

Parses a JSON string into a native PHP array or object.
Throws a `TransformException` if the input is not stringable or not valid JSON.

---

### CastTo\IfNull

**Arguments:** `mixed $fallback = false`

Replaces `null` with the given default value.

---

### CastTo\Integer

**Arguments:** `IntCastMode $mode = IntCastMode::Trunc`

Casts the value to an integer using a strategy: `Ceil`, `Floor`, `Round`, or `Trunc`.

---

### CastTo\Join

**Arguments:** `string $separator = ','`

Converts an array of strings into a comma-separated string.
Throws a `TransformException` if the input is not an array.

---

### CastTo\Json

**Arguments:** `int $flags = 0, int $depth = 512`

Converts the value to a JSON string using `json_encode()`.
Throws a `TransformException` on failure.

---

### CastTo\JsonExtract

**Arguments:** `string $path`

Extracts a specific key or subfield from a JSON string or any array, or nested array structure. Object traversal isn't supported yet.
The `$path` can use dot-notation for nested fields (e.g. `"user.name"`).
Throws a `TransformException` if the input is not an array or a valid JSON string, or the path does not exist.

---

### CastTo\LocalizedCurrency

**Arguments:**
- `int $style = \NumberFormatter::CURRENCY`
- `int $precision = 2`
- `?string $locale = null` ([see resolution](#parameter-resolution))
- `?string $currency = null`

Formats a number as a locale-aware currency string using `NumberFormatter`.
Throws if the input is not numeric.

---

### CastTo\LocalizedDateTime

**Arguments:**
- `int $dateStyle = \IntlDateFormatter::MEDIUM`
- `int $timeStyle = \IntlDateFormatter::SHORT`
- `?string $locale = null` ([see resolution](#parameter-resolution))
- `?string $timezone = null` ([see resolution](#parameter-resolution))

Formats a `DateTimeInterface` using a locale-aware format.
Throws if input is not a valid date/time object.

---

### CastTo\LocalizedNumber

**Arguments:**
- `int $style = \NumberFormatter::DECIMAL`
- `int $precision = 2`
- `?string $locale = null` ([see resolution](#parameter-resolution))

Formats a float as a locale-aware number string.
Throws a `TransformException` if the input is not numeric.

---

### CastTo\NumericString

**Arguments:**
- `int $decimals = 0`
- `string $decimalPoint = '.'`
- `string $thousandsSeparator = ''`

Formats a numeric value into a string with configurable decimal and thousands separators.
Throws a `TransformException` if the input is not numeric.

---

### CastTo\NullIf

**Arguments:** `mixed $when`

Replaces a given value (or any of several) with `null`.

---

### CastTo\RegexReplace

**Arguments:** `string $pattern, string $replacement`

Performs a regex search and replace on the string value.
Throws a `TransformException` if the regex operation fails or if input is not stringable.

---

### CastTo\RegexSplit

**Arguments:** `string $pattern`, `int $limit = -1`

Splits a string using a regular expression.
Returns an array of substrings.
Throws a `TransformException` if input is not stringable or if the regex fails.

---

### CastTo\RemoveDiacritics

**Arguments:** `bool $useIntlExtension = true`

Removes diacritical marks (accents) from characters.
Uses Transliterator if available, or falls back to a basic ASCII transliteration.

---

### CastTo\ReplaceIf

**Arguments:** `mixed $when`, `mixed $then = null`

If the input matches `$when`, replaces it with `$then`.

---

### CastTo\Rounded

**Arguments:** `int $precision = 0`

Rounds a float to the nearest value at the given precision.
Throws a `TransformException` on invalid input.

---

### CastTo\Slug

**Arguments:** `string $separator = '-'`

Converts a string into a URL-friendly slug.
Relies on the Intl extension to strip diacritics.
Throws a `TransformException` if input is not stringable or if Intl is unavailable.

---

### CastTo\Split

**Arguments:** `string $separator = ','`

Splits a CSV string into an array.
Throws a `TransformException` if the input cannot be cast to a string.

---

### CastTo\Coalesce

**Arguments:** `array|\Traversable $ignore = [null], mixed $fallback = (none)`

Returns the first element in the input array/iterable that is not in `$ignore`.
If none is found, returns `$fallback` when provided; otherwise throws a `TransformException`.

---

### CastTo\Str

Casts the value to a string using `(string)` coercion.

---

### CastTo\Pad

**Arguments:** `int $length, string $char = ' ', int $padType = STR_PAD_RIGHT`

Pads a string to the given length, matching `str_pad()` semantics.
Throws a `TransformException` if the input is not stringable.

---

### CastTo\CardScheme

**Arguments:** `string|array|null $schemes = null`

Detects the card scheme for the input and returns the scheme name.
If `$schemes` is provided, only those schemes are considered.

---

### CastTo\Trimmed

**Arguments:** `string $characters = " \n\r\t\v\x00", string $where = 'both'`

Trims whitespace (or specified characters) from the string.
Throws a `TransformException` if the input is not stringable.

---

## <a id="parameter-resolution"></a>📘 Parameter Resolution

For specific parameters, casters may use the trait `UsesParamResolver` or derived traits* to allow flexible, cast-time resolution. This is the case for `locale` and `timezone` parameters in core casters.

### 🔍 Resolution Priority

Examples given for parameter `"locale"`
| | Case                     | Resolved from                                                  |
|-|--------------------------|-----------------------------------------------------------------|
|1| `'fr_CH'` (string)       | Used directly                                                   |
|2| `Provider::class`        | Calls `Provider::getLocale($value, $prop, $dto)`                   |
|3| `'<context'` string      | Calls `$dto->getContext('locale')`                                 |
|4| `'<dto'` string         | Calls `$dto->getLocale()`                                          |
|5| `null`                   | Fallback order:<br>1. context → 2. dto getter → 3. fallback()   |

Visit [Crafting Casters](CraftingAttributeCasters.md) for more info on how to support flexible parameter resolution in your own casters.

*Existing derived traits: `UsesLocaleResolver`, `UsesTimezoneResolver`
