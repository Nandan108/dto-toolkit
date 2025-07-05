# Mapping Attributes

Mapping attributes define how DTO properties map to external data structures during hydration (input) or normalization (output).

---

## 🔁 `#[MapFrom($paths)]` (Inbound Mapping)

Use `MapFrom` to extract one or more values from the input array or other roots (like the DTO or its context) using a [prop-path](https://github.com/nandan108/prop-path) expression.

### 💡 Basic usage

```php
#[MapFrom('postal_code')]
public string $zip;

#[MapFrom(['zip' => 'address.zip', 'city' => 'address.city'])]
#[CastTo\newInstance(Address::class)]
public Address $address;
```

This allows precise decoupling of DTO structure from raw input shape.

---

### 🧠 Path syntax (powered by `prop-path`)

Paths are parsed and evaluated using `prop-path` ([syntax here](https://github.com/Nandan108/prop-path/blob/main/docs/Syntax.md)). It supports expressions such as::

* Access multiple **roots**:
  * `$input`: the input array (default root)
  * `$dto`: the DTO instance itself
  * `$context`: the DTO’s context (see `HasContextInterface`)
* Use fallback:
  `'user.email ?? user.backup_email'`
* Require keys:
  `'!email'` (throws if key missing), `'!!email'` (throws if null)
* Extract groups of values:
  `'[foo, bar.baz, qux.0.name]'`
* Access arrays by index or slices:
  `'items[0:3].*.name'`

### ❗ Error handling

* Invalid syntax → `ExtractionSyntaxError`
* Missing required fields → `LoadingException`
* All paths are validated at DTO construction

---

## 🔄 `#[MapTo($outboundName, $setterName = null)]` (Outbound Mapping)

Use `MapTo` to rename or discard properties during outbound transformation (DTO → array or entity):

```php
#[MapTo('postalCode')]
public string $zip;

#[MapTo(null)]
public mixed $internalDebugField; // ignored in output

#[MapTo('address', setterName: 'assignAddress')]
public Address $address;

```

---

## 📝 Notes

* `MapFrom` and `MapTo` may be used independently or together
* `MapFrom` runs **before** any casting or normalization
* `MapTo(null)` discards the property completely (e.g. internal use only)
* The `setterName` parameter is only used when hydrating entities with `$dto->toEntity()`. In the rare cases where your property setter doesn't use the standard `"set".$propName` method name, you can specify the exact setter method name this way. It is ignored when exporting content with `$dto->toOutboundArray()`.

---

## 📌 Example

```php
class AddressDto extends FullDto
{
    #[MapFrom('zip_code')]
    #[MapTo('postalCode')]
    public ?string $zip;

    #[MapFrom(['zip' => 'address.zip', 'city' => 'address.city'])]
    #[CastTo(AddressCast::class)]
    #[MapTo('address', setter: 'assignAddress')]
    public null|array|Address $address;

    #[MapFrom('$context.userId')]
    #[MapTo(null)]
    public ?int $traceId;
}
```

This mapping system provides powerful, precise control over how data flows into and out of your DTOs — with clear, declarative syntax.

