# Attributes

## Table of contents

- Processing chain nodes
  - #[Mod\\*] - [see Chain modifiers](BuiltInModifiers.md)
  - #[CastTo\\*] - [see Casters](BuiltInCasters.md)
  - #[Assert\\*] - [see Validators](BuiltInValidators.md)

- Mapping in and out
  - [#[MapFrom($paths)]](#map-from)
  - [#[MapTo($outboundName, )]](#map-to)

- Scoping groups - [see Processing/Property-Level Groups](Processing.md#prop-groups)
  - #[PropGroups]
  - #[WithDefaultGroups]

- Other
  - #[Inject] - [see Dependency Injection](DI.md)
  - #[Outbound] - [See Dto Lifecycle](DtoLifecycle.md)
  - [#[Presence]](#presence)

## Mapping Attributes

Mapping attributes define how DTO properties map to external data structures during hydration (input) or normalization (output).

---
<a id='map-from'></a>

### 🔁 `#[MapFrom($paths)]` (Inbound Mapping)

Use `MapFrom` to extract one or more values from the input array or other roots (like the DTO or its context) using a [prop-path](https://github.com/nandan108/prop-path) expression.

#### 💡 Basic usage

```php
#[MapFrom('postal_code')]
public string $zip;

#[MapFrom(['zip' => 'address.zip', 'city' => 'address.city'])]
#[CastTo\newInstance(Address::class)]
public Address $address;
```

This allows precise decoupling of DTO structure from raw input shape.

---

#### 🧠 Path syntax (powered by `prop-path`)

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

#### ❗ Error handling

* Invalid syntax → `ExtractionSyntaxError extends ConfigException`
* Missing required fields → `ExtractionException extends ProcessingException`
* All paths are validated at DTO construction

Note that if the data item sought is missing (e.g. no zip entry in address array, or address is a string rather than a container), then by default the PropPath mapper will fail, the property's value will be considered missing from input. This can be adjusted via MapFrom's second argument, `$defaultThrowMode`:
```php
MapFrom(
    string | array $paths,
    ThrowMode $defaultThrowMode = ThrowMode::MISSING_KEY,
)
```

---

<a id='map-to'></a>

### 🔄 `#[MapTo($outboundName, $setterName = null)]` (Outbound Mapping)

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

### 📝 Notes

* `MapFrom` and `MapTo` may be used independently or together
* `MapFrom` runs **before** any casting or normalization
* `MapTo(null)` discards the property completely (e.g. internal use only)
* The `setterName` parameter is only used when hydrating entities with `$dto->exportToEntity()`. In the rare cases where your property setter doesn't use the standard `"set".$propName` method name, you can specify the exact setter method name this way. It is ignored when exporting content with `$dto->toOutboundArray()`.

---

### 📌 Example

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

---

### 🧭 `#[DefaultOutboundEntity($entityClass, $groups = [])]`

Sets the default entity class used by `exportToEntity()` when no explicit target is provided.
If `groups` are supplied, the DTO must implement `HasGroupsInterface` and the entity will only
be selected when the groups are in scope for the outbound export phase.

```php
#[DefaultOutboundEntity(UserEntity::class)]
final class UserDto extends FullDto
{
    public int $id = 0;
}
```

---

### InstantiatesEntityInterface<a id='InstantiatesEntityInterface'></a>

For advanced or conditional export scenarios, DTOs may implement `InstantiatesEntityInterface`
to take full control over entity instantiation during outbound export.

This interface is consulted **last in the resolution order** when exporting to an entity if:
1. no explicit entity class name or pre-build object is provided
2. no `#[DefaultOutboundEntity]` matches the active scope

```php
interface InstantiatesEntityInterface
{
    /**
     * @param array<string, mixed> $propsToSet
     * @return array{0: object, 1: bool}
     */
    public function newEntityInstance(array $propsToSet): array;
}
```

The method must return:

* the instantiated entity
* a boolean indicating whether properties are already hydrated

#### Scope-aware instantiation

Because this method is executed within an active processing context,
it may inspect the current scope or context to adapt behavior:

```php
if ($this->groupsAreInScope(Phase::OutboundExport, ['api'])) {
    return [new ApiUserEntity(), false];
}

return [new DomainUserEntity(), false];
```

This allows:

* conditional entity selection
* constructor-based hydration
* framework- or adapter-specific instantiation
* clean separation between declarative mapping and imperative construction logic

---

## Presence Policy <a id='presence'></a>

Sets how **input presence vs. null vs. missing** affects a property's filled status when loading data. The policy drives whether a property is marked as "filled".

- `PresencePolicy::Default` (implicit): mark filled when input is present, including `null`.
- `PresencePolicy::NullMeansMissing`: if input is `null`, treat it as missing (do not mark filled).
- `PresencePolicy::MissingMeansDefault`: always mark as filled even when input is absent; keeps the property's default value.

Usage:

```php
use Nandan108\DtoToolkit\Attribute\Presence;
use Nandan108\DtoToolkit\Enum\PresencePolicy;

#[Presence(PresencePolicy::NullMeansMissing)] // class-level default
final class SignupDto extends BaseDto {
    #[Presence(PresencePolicy::MissingMeansDefault)]
    public string $country = 'US'; // remains filled with default when absent

    public ?string $middleName = null; // class policy: null input is treated as missing
}
```

Notes:

- The attribute can be applied at the **class** level (default for all props) or **property** level (override).
- This is a load-time policy, running before processing (casting/validation); it only controls the "filled" flag and whether a missing value falls back to the property's default.
