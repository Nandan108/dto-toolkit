# 📚 Processing: Validating and Transforming Data Through Nodes

DTO Toolkit uses **processing nodes** to transform and validate property values during the **inbound** (normalization) and **outbound** (export) phases.

Processing nodes are produced by attributes (`CastTo`, `Validate`, modifiers) and compiled into **processing chains** that are executed automatically when data flows into or out of a DTO.

This document describes how processing works in DTOT Core, including casters, validators, chain modifiers, node production, phases, groups, caching, dependency injection, lifecycle hooks, and **error handling modes**.

---

# 1. Processing Nodes: The Core Abstraction

A **processing node** is a small, composable step in a transformation pipeline.

Each node:

- Exposes a `Closure` that takes a value and returns a transformed value
  (or throws a `ProcessingExceptionInterface` on failure)
- May hold an underlying instance (caster, validator, modifier logic)
- May be chained with other nodes

Every node implements:

```
Contracts\ProcessingNodeInterface
```

This interface allows nodes to be composed recursively into a `ProcessingChain`.

---

# 2. Processing Node Producers (Attributes)

A **processing node producer** is an attribute placed on a DTO property that creates a processing node.

Producers implement:

```
Contracts\ProcessingNodeProducerInterface
```

The main producer kinds are:

### **Casters**

Declared via `#[CastTo\Xyz]` or `#[CastTo(ClassName::class)]`.
Casters transform values.

### **Validators**

Declared via `#[Validate\Xyz]`.
Validators validate values and **throw** on invalid input, but return the value unchanged otherwise.

Validators participate fully in processing chains and in the new **error-collection system**.

### **Modifiers**

Attributes under `Nandan108\DtoToolkit\Attribute\ChainModifier` (usually imported as `Mod`).
Modifiers alter how chains are composed (e.g. error wrapping, per-item behavior, chain slicing).

---

# 3. Declaring Processing Nodes

DTOT supports several declaration styles for node producers.

## 3.1 Attribute Classes (Recommended)

```php
use Nandan108\DtoToolkit\CastTo;

class ProductDto extends FullDto {
    #[CastTo\Trimmed]
    #[CastTo\Slug(separator: '-')]
    public ?string $title;

    #[Validate\NotNull]
    #[Validate\Length(min: 3)]
    public ?string $code;
}
```

Attribute classes are expressive, type-safe, discoverable, testable, and support DI.

#### Argument Syntax

For casters that take arguments and more particularely multiple ones, it is advised to use the named argument syntax for clarity.

Note that that PHP Attribute parameters may only contain scalars, arrays, constants, and constant expressions. Anything else is considered invalid by the PHP parser.

---

## 3.2 Class-String Node Producers

```php
#[CastTo(MyCaster::class, args: [...], constructorArgs: [...])]
public string $value;
```

DTOT instantiates the class which must implement CasterInterface or ValidatorInterface, injecting services when needed.

---

## 3.3 DTO Method-Based Producers

```php
#[CastTo('slug', args: ['-'])]
public string $name;

public function castToSlug(string $v, $sep): string { ... }
```

Resolved via `castTo<MethodName>` convention.

---

# 4. Processing Chains

For each property, a chain is built from its list of producer attributes.

```
[ node A ] → [ node B ] → [ node C ]
```

Nodes are represented internally as `ProcessingNodeMeta` objects.
All nodes are composed into a single `Closure` for execution.

Separate chains are built for:

- inbound phase
- outbound phase
- active groups

---

# 5. Chain Modifiers

```php
use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;
```

Modifiers alter the behavior of node production or chaining.
They treat casters and validators uniformly and wrap nodes to apply custom control-flow semantics.

Examples:

### `#[Mod\FailTo($fallback)]`

Wraps all upstream nodes and returns `$fallback` if any of them fail.

### `#[Mod\FailNextTo($fallback, $count=-1)]`

Wraps downstream nodes in the chain.

### `#[Mod\PerItem]`

Applies nodes element-wise to array items.

```php
#[CastTo\Split]             // default separator: ','
#[Mod\PerItem(3)]           // apply next three nodes per-array-item
    #[CastTo\Floating]      // string → float
    #[CastTo\Rounded(2)]    // round to 2 decimals
    #[Mod\FailNextTo(10), Validate\Range(max: 10)] // cap values at 10
#[CastTo\Join(';')]
public string $prices = '5.555,12.345,0'; // → '5.56;10;0'
```

Click to see the full list of [built-in modifiers](BuiltInModifiers.md)

---

# 6. Phases: Inbound & Outbound

DTOT is phase-aware via the `Phase` enum.

### Inbound phase

Triggered when building the DTO from external data:

- `fromArray()`
- `fromEntity()`
- framework adapters (`fromRequest`, `fromModel`, etc.)

### Outbound phase

Triggered when exporting DTOs:

- `toOutboundArray()`
- `toEntity()`
- framework adapters (`toResponse`, etc.)

Processing nodes and chain modifiers are filtered by phase (and by active groups)
before chain compilation.

By default, processing nodes produced by attributes apply to the **inbound** phase.
If a property includes the `#[Outbound]` marker, then **all attributes that appear _after it_** on that property are assigned to the **outbound** phase instead (e.g., to format values for API output).

A single property may therefore declare both inbound and outbound processing nodes:

```php
#[CastTo\DateTimeFromFormat('Y-m-d H:i:s')]   // inbound processing
#[Outbound]                                   // phase switch
#[CastTo\DateTimeToFormat('c')]               // outbound processing
public null|string|\DateTimeInterface $createdAt;
```

# 7. Groups

Groups allow conditional inclusion of **properties** and **processing nodes** when normalizing DTOs.
Groups are activated using the fluent `withGroups()` method:

```php
$dto = MyDto::withGroups('api')->fromArray($input);
```

or on an existing instance:

```php
$dto = (new MyDto())->withGroups('admin');
```

Groups apply globally for the duration of the inbound or outbound operation.
Both inbound and outbound chain compilation respect the active group set.

## 7.1 Class-Level Default Groups: `#[WithDefaultGroups(...)]`

`#[WithDefaultGroups]` preloads group scopes automatically when a DTO is instantiated through DTOT factories (`newInstance()`, `fromArray()`, `fromDto()`, adapters, etc.). It accepts the same parameters as `withGroups()`:

- `all` — baseline groups applied to every phase unless overridden
- `inbound` / `inboundCast` — inbound IO vs. inbound processing
- `outbound` / `outboundCast` — outbound IO vs. outbound processing

Each argument may be a string or an array; unspecified values fall back to `all`, then to their IO phase. The attribute may only be used on DTOs implementing `HasGroupsInterface` (e.g., `FullDto`); otherwise an `InvalidConfigException` is thrown on instantiation. You can still override defaults at runtime with `withGroups()`.

```php
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;
use Nandan108\DtoToolkit\Attribute\Outbound;
use Nandan108\DtoToolkit\Attribute\WithDefaultGroups;
use Nandan108\DtoToolkit\Core\FullDto;

#[WithDefaultGroups(all: 'api', inbound: 'admin', inboundCast: 'strict')]
final class UserDto extends FullDto {
    #[Mod\Groups('admin'), CastTo\SnakeCase]                     // inbound cast: strict/admin
    #[Outbound, Mod\Groups('api'), CastTo\RegexReplace('/^/', 'prefix_')] // outbound cast: api
    public ?string $tag = null;
}

$dto = UserDto::fromArray(['tag' => 'Some Value']);         // defaults applied automatically
$dto = UserDto::withGroups('partner')->fromArray($payload); // override defaults when needed
```

---
<a id='prop-groups'></a>

## 7.2 Property-Level Groups: `#[PropGroups([...])]`

`#[PropGroups]` controls whether the **property itself** participates in:

- inbound loading
- inbound processing
- outbound processing
- outbound export

If no active groups match:

- the property is considered **out of scope**
- its processing chain is **not built**
- the property is excluded from input/output entirely

```php
use Nandan108\DtoToolkit\Attribute\PropGroups;

class UserDto extends FullDto {
    #[PropGroups(['admin'])]
    public ?int $roleId;
}

MyDto::withGroups('public')->fromArray($input);  // roleId excluded
MyDto::withGroups('admin')->fromArray($input);   // roleId included
```

`#[PropGroups]` is **coarse-grained**: it controls **whether the property exists** in the current projection.

---

## 7.3 Node-Level Groups: `#[Mod\Groups(groups: [...], count: N = 1)]`

`#[Mod\Groups]` applies grouping to **processing nodes** (casters, validators, modifiers), not to the property itself.

- It affects the next **N** node producers on the property.
- If group conditions don’t match, only those nodes are skipped.
- The property itself remains active as long as `#[PropGroups]` allows it.

```php
use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;

class ProductDto extends FullDto {
    #[Mod\Groups(['api'], 2)]
      #[CastTo\Trimmed]        // applies only for 'api'
      #[Validate\Email]        // applies only for 'api'
    #[CastTo\Slug]             // always applies
    public string $contactEmail;
}

ProductDto::withGroups('api')->fromArray($input);   // Trimmed + Email + Slug
ProductDto::withGroups('web')->fromArray($input);   // only Slug
```

`#[Mod\Groups]` is **fine-grained**: it controls **which processing steps run**, not whether the property exists.

---

## 7.4 Combined Use

You can combine both mechanisms for fully expressive behavior:

```php
class AdminUserDto extends FullDto {
    #[PropGroups(['admin'])]
    #[Mod\Groups(['strict'])]
        #[Validate\Required] // only when 'strict' group is active
    #[CastTo\Integer]
    public string|int $roleId;
}

AdminUserDto::withGroups('public')->fromArray($input);        // property excluded
AdminUserDto::withGroups('admin')->fromArray($input);         // included, Required skipped
AdminUserDto::withGroups(['admin', 'strict'])->fromArray($i); // included, Required applied
```

---

## 7.5 Summary

| Attribute       | Applies To       | Controls                         | Granularity |
| --------------- | ---------------- | -------------------------------- | ----------- |
| `#[PropGroups]` | Entire property  | Whether the property is included | Coarse      |
| `#[Mod\Groups]` | Processing nodes | Whether specific nodes execute   | Fine        |

Both inbound and outbound processing chains respect these group filters when resolving active nodes.

---

# 8. Node Resolution & Caching

Node resolution applies this precedence:

1. Attribute instance implements the interface → use it
2. `$methodOrClass` is a class → instantiate, with DI if available
3. `$methodOrClass` references a DTO method → build method node
4. Custom resolver (`NodeResolverInterface`) → delegate
5. Otherwise → throw

### Node Caches

1. **Node resolution cache** (one instance per node producer class + constructor args)
2. **Bootable lifecycle** (one-time boot per instance)
3. **Chain cache** (per DTO class, phase, active groups)

---

# 9. Dependency Injection

Node classes may declare injectable properties using:

```
#[Injected]
```

`CastBase` and `ValidateBase` support framework-specific injection via adapters.
Nodes may also implement `BootsOnDtoInterface` to perform one-time initialization.
See [DI.md](DI.md) for more details.

---

# 10. Error Handling

## Failure Modes

Processing nodes (casters, validators, modifiers) signal failure via:

```
Process\ProcessingExceptionInterface
```

DTOT supports **four runtime error-collection modes** via the `ErrorMode` enum:

| Error Mode             | Behavior                                                                                                                                                            |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **FailFast**           | Default. Exceptions are thrown immediately and processing stops.                                                                                                    |
| **CollectFailToInput** | Exceptions are recorded, but the original input value is preserved.                                                                                                 |
| **CollectFailToNull**  | Exceptions are recorded, and the property value is set to `null`.                                                                                                   |
| **CollectNone**        | Exceptions are recorded, and the property is omitted entirely:<br>- inbound: property is marked "unfilled"<br>- outbound: property does not appear in output arrays |

The default mode can be configured:

```php
// Globally
BaseDto::setDefaultErrorMode(ErrorMode::CollectFailToNull);
// At DTO level
$dto->setErrorMode(ErrorMode::CollectFailToNull);
```

These modes apply **uniformly** to inbound and outbound chains.

## Error Collection

Errors may be captured by passing a ProcessingErrorList:

```php
$errors = new ProcessingErrorList();

$dto = MyDto::fromArray(
    $input,
    errorList: $errors,
    errorMode: ErrorMode::CollectFailToNull
);
```

`ProcessingErrorList` implements `IteratorAggregate` and `Countable`

Adapters may convert collected errors into their native structures, e.g.:

- Symfony → `ConstraintViolationListInterface`
- Laravel → `MessageBag`

## Failure behavior by `ErrorMode` and phase

| Error Mode         | Inbound phase             | Outbound phase            |
| ------------------ | ------------------------- | ------------------------- |
| FailFast           | throw immediately         | throw immediately         |
| CollectFailToInput | preserve original input   | return raw property value |
| CollectFailToNull  | set property to null      | output null               |
| CollectNone        | mark property as unfilled | omit property from output |

---

# 11. Naming Conventions

### Casters

- Core: `CastTo\Xyz`
- Adapters: `Casts\ToXyz`
- Projects: `Cast\ToXyz`

### Validators

- Core: `Validate\Xyz`
- Adapters: `Validates\Xyz`

### Modifiers

- Namespace: `Mod\*`

---

# 12. Debugging

Tools (current and planned):

- chain dumping
- node metadata inspection
- structured exception debugging with context
- debug mode surfacing phase/group/node provenance

---

# Summary

Processing in DTO Toolkit is built on three core concepts:

- **Node producers** — attributes that create casters, validators, and modifiers
- **Processing nodes** — closures that implement transformation or validation
- **Processing chains** — composed transformations executed during inbound and outbound phases

Error modes (`ErrorMode`) and error collection (`ProcessingErrorList`) provide flexible runtime behavior for production systems and framework adapters.

This unified processing model supports powerful, composable transformations and first-class validation, and forms the foundation of DTOT Core and its adapter ecosystem.
