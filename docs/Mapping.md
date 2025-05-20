# Mapping Attributes

Mapping attributes define how DTO properties should be transformed during hydration (input) or normalization (output).

> #### Note: this feature is NOT ready
> When it is, add this line to [README](../README.md)/Documentation:
> - [Mapping](docs/Mapping.md) – customize input and output field mappings using `MapFrom()` and `MapTo()`

---

## 🔁 `#[MapFrom(...)]` (Inbound Mapping)

Use `MapFrom` to specify one or more raw input fields that should map to a given property:

```php
#[MapFrom('postal_code')]
public string $zip;

#[MapFrom(['zip' => 'postal_code', 'city' => 'city'])]
#[CastTo\newInstance(Address::class)]
public Address $address;
```

This allows separation of *input field names* from *internal DTO structure* and decouples mapping from casting.

---

## 🔄 `#[MapTo(...)]` (Outbound Mapping)

Use `MapTo` to control how DTO properties are mapped to specific output properties.

### 💡 Syntax

```php
class MapTo {
    public function __construct(
        ?string $target = null,
        ?string $setter = null,
        bool $silentFail = false
    ) { ... }
}

#[MapTo('targetField', setter: 'customSetter')]
public string $sourceField;
```

### 🧠 Resolution Order

| Form                                      | Behavior                                                                 |
|------------------------------------------|--------------------------------------------------------------------------|
| `#[MapTo('field', setter: 'assignX')]`    | Try `$target->assignX($value)`, then `$target->field = $value`, then throw*.|
| `#[MapTo(setter: 'assignX')]`             | Try `$target->assignX($value)` only — throw if not found*                 |
| `#[MapTo('field')]`                       | Try `$target->setField($value)`, then `$target->field = $value`, then throw* |
| `#[MapTo(null)]`                          | Discards the property entirely during outbound transformation            |

*If `$silentFail` is true, it will not throw an error when unable to set.

This offers granular control for both structured output and DTO → Entity hydration.

---

## 🔒 Notes

- Both `MapTo` and `MapFrom` can be used on the same property
- `MapFrom` mapping logic is evaluated *before* any casting or normalization
- `MapTo(null)` is equivalent to sending a value to `/dev/null`
- If both setter and field assignment fail (public prop/setter unavailable), a `MappingException` will be thrown

---

## 📌 Example

```php
class AddressDto extends FullDto
{
    #[MapFrom('zip_code')]
    #[MapTo('postalCode')]
    public ?string $zip;

    #[MapFrom(['address', 'zip', 'city'])]
    #[CastTo(AddressCast::class)]
    #[MapTo(setter: 'assignAddress')]
    public null|array|Address $address;

    #[MapTo(null)]
    public ?string $debugInfo = null;
}
```

This setup allows total control over input field mapping and output transformation while maintaining a clean, declarative style.
