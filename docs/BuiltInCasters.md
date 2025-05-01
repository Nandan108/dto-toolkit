# Built-in Caster Attributes

This document lists all built-in value transformation attributes (`CastTo`) provided by the DTO Toolkit.

---

## 🎯 About CastTo Attributes

**CastTo attributes** transform individual property values during the DTO's lifecycle.

They are typically applied when:
- Normalizing inbound data (e.g., from a request or form)
- Preparing outbound data (e.g., for API responses or database persistence)

Each caster:
- Accepts and processes a **single value**
- Either **transforms** or **sanitizes** the value
- Throws a `CastingException` if the input is invalid or can't be transformed

These attributes can be freely combined and chained to compose complex transformations.

---

## 🏷️ Available CastTo Attributes

### Case converters

**Arguments:** _none_
All throw a `CastingException` if the input is not stringable.

#### Standard lower/upper case

- CastTo\\**Lowercase**: `"postalCode"` ➔ `"postalcode"`
- CastTo\\**Uppercase**: `"postalCode"` ➔ `"POSTALCODE"`

#### Identifier Casing Styles

Words in input value may be separated by non-letter characters ***or*** a change of case.

- CastTo\\**CamelCase**:  `"postal_code"` ➔ `"postalCode"`
- CastTo\\**PascalCase**:  `"postal_code"` ➔ `"PostalCode"`
- CastTo\\**KebabCase**:  `"postalCode"` ➔ `"postal-code"`
- CastTo\\**SnakeCase**:  `"PostalCode"` ➔ `"postal_code"`
- CastTo\\**UpperSnakeCase**:  `"PostalCode"` ➔ `"POSTAL_CODE"`

---

### CastTo\Boolean

Casts truthy/falsy values to boolean.
For string inputs, uses `filter_var($value, FILTER_VALIDATE_BOOLEAN)`.
Throws a `CastingException` on invalid input.

---

### CastTo\Capitalized

Capitalizes the first letter of a string.
Throws a `CastingException` if the input is not stringable.

---

### CastTo\DateTime

**Arguments:** `string $format = 'Y-m-d H:i:s'`

Parses a datetime string into a `\DateTimeImmutable` instance.
Throws a `CastingException` if parsing fails.

---

### CastTo\Enum

**Arguments:** `string $enumClass`

Casts a scalar value to a PHP backed enum.
Throws an `InvalidArgumentException` if `$enumClass` is invalid.
Throws a `CastingException` if the value does not match any enum case.

---

### CastTo\Floating

Casts a numeric string or int to float.
Throws a `CastingException` on invalid input.

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
Throws a `CastingException` if the input is not an array.

---

### CastTo\JsonEncode

**Arguments:** `int $flags = 0, int $depth = 512`

Converts the value to a JSON string using `json_encode()`.
Throws a `CastingException` on failure.

---

### CastTo\NumericString

**Arguments:**
- `int $decimals = 0`
- `string $decimalPoint = '.'`
- `string $thousandsSeparator = ''`

Formats a numeric value into a string with configurable decimal and thousands separators.
Throws a `CastingException` if the input is not numeric.

---

### CastTo\NullIf

**Arguments:** `mixed $when`

Replaces a given value (or any of several) with `null`.

---

### CastTo\RegexReplace

**Arguments:** `string $pattern, string $replacement`

Performs a regex search and replace on the string value.
Throws a `CastingException` if the regex operation fails or if input is not stringable.

---

### CastTo\RemoveDiacritics

**Arguments:** `bool $useIntlExtension = true`

Removes diacritical marks (accents) from characters.
Relies on PHP's Intl extension (`Transliterator`) if $useIntlExtension is true amd Intl is available.
Otherwise, falls back to using strtr() with a hard-coded transliteration map.

---

### CastTo\ReplaceIf

**Arguments:** `mixed $when, mixed $then = null`

If the input matches `$when`, replaces it with `$then`.

---

### CastTo\Rounded

**Arguments:** `int $precision = 0`

Rounds a float to the nearest value at the given precision.
Throws a `CastingException` on invalid input.

---

### CastTo\Slug

**Arguments:** `string $separator = '-'`

Converts a string into a URL-friendly slug.
Relies on the Intl extension to strip diacritics.
Throws a `CastingException` if input is not stringable or if Intl is unavailable.

---

### CastTo\Split

**Arguments:** `string $separator = ','`

Splits a CSV string into an array.
Throws a `CastingException` if the input cannot be cast to a string.

---

### CastTo\Str

Casts the value to a string using `(string)` coercion.

---

### CastTo\Trimmed

**Arguments:** `string $characters = " \n\r\t\v\x00", string $where = 'both'`

Trims whitespace (or specified characters) from the string.
Throws a `CastingException` if the input is not stringable.
