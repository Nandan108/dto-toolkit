# Casters

Casters are a central feature of the DTO Toolkit. They allow you to modify the shape of:

- **Inbound data**, after validation and before hydrating a DTO
- **Outbound data**, before exporting from the DTO (to array, entity or model)

The goal of this project is to provide expressive, declarative syntax for writing lean DTOs and clean controller logic.

---

## 🔄 Inbound vs Outbound Casting

The `#[CastTo(...)]` attribute supports an optional `outbound: true` flag to indicate **when** the casting should occur:

- **Inbound casting** (default) applies when the DTO is being created from raw input, such as request data or an array (`fromArray()`)
- **Outbound casting** applies when transforming the DTO back to an entity or output format (`toEntity()`, `toOutboundArray()`)

This separation allows for clean and flexible data flows:

- Sanitize or type-convert values on the way **in**
- Format or enrich values on the way **out**

### ✅ Example: Outbound Casting Only

```php
#[CastTo\DateTime(format: 'Y-m-d H:i:s')]
public ?string $createdAt = null;
```

---

## Declaring and Using Casters

The DTO Toolkit supports **three styles** of declaring casters.

Each offers different trade-offs in reusability, expressiveness, and discoverability.

---

### 1. ✅ Attribute Casters (Recommended DX)

Use predefined attribute classes for each transformation. These are expressive, type-safe, and auto-discoverable.

```php
use Nandan108\DtoToolkit\CastTo;

class MyDto extends FullDto {
    #[CastTo\Trimmed]
    public ?string $name;

    #[CastTo\Slug('~')]
    public ?string $title;

    #[CastTo\Rounded(2)]
    public float|string|null $price;
}
```

This syntax mimics Symfony Validator or Serializer attributes.

#### Argument Syntax

For casters that take arguments and more particularely multiple ones, it advised to use the named argument syntax for clarity.

```php
   #[CastTo\Rounded(precision: 2)]
   public float|string|null $price;

   #[CastTo\ReplaceIf(when: ['foo','bar'], then: 'baz')]
   public ?string $price;
```

Note that that Attribute parameters may only contain scalars, arrays, constants, and constant expressions. Anything else will not be concidered valid.

---

### 2. 🛠️ Custom Caster Classes (`CasterInterface`)

Define reusable casting logic in your own class.

```php
#[CastTo(MyCustomCaster::class, args: ['param'], constructorArgs: [])]
public string $value;
```

```php
class MyCustomCaster implements CasterInterface {
    public function cast(mixed $value, array $args = []): mixed {
        // custom logic here
    }
}
```

If `constructorArgs` are not provided, the system may fall back to container-based resolution (adapter-defined).

---

### 3. ⚙️ Method-Based Caster (String Identifier)

Lightweight syntax that calls a method on the DTO or the core `CastTo` class.

```php
#[CastTo('slug', args: ['separator'])]
public string $slug;

public function castToSlug(string $value, string $separator = '-') {
    return Str::slug($value, $separator);
}
```

This is useful for one-off transformations where defining a full class would be overkill.

---

## 🔗 Caster Chaining

You can now apply multiple `#[CastTo(...)]` attributes to a single property.
They will be executed **in order**, allowing you to build rich transformation pipelines.

```php
#[CastTo\Trimmed]
#[CastTo\Slug('-')]
public ?string $title;
```

This trims the input string, then slugifies it.

Chaining also supports **modifiers** that affect how casters are applied.

---

### 🧩 Modifier Attributes

Modifier attributes implement the `CastModifier` interface and control the behavior of the **caster chain**.

You can place modifiers before or after casters depending on their function:

---

#### 📚 Example: `#[PerItem]`

Apply the next N casters to each item in an array.

```php
#[CastTo\ArrayFromCsv]
#[PerItem(2), CastTo\Floating, CastTo\Rounded(2)]
#[CastTo\CsvFromArray(';')]
public string $prices = '10,12.45533,0';
```

This:

1. Parses the CSV into an array
2. Applies float conversion + rounding to each item
3. Implodes the array back to CSV with `;`

---

#### 🧯 Example: `#[FailTo]` _(post-modifier)_

Catch any exceptions thrown by previous casters and return a fallback value:

```php
#[CastTo\JsonDecode]
#[CastTo\Dto(AddressDto::class)]
#[FailTo(fallback: [], handler: 'handleDtoFailure')]
```

This wraps all prior casting in a try/catch.
If an error occurs, `handleDtoFailure(Throwable $e, mixed $fallback)` is called on the DTO.

---

#### 🧷 Example: `#[FailNextTo]` _(pre-modifier)_

Wraps only the **next** caster in a try/catch:

```php
#[FailNextTo('n/a')]
#[CastTo\Floating]
```

---

#### 🛠️ Writing Your Own Modifiers

To define a custom modifier, implement:

```php
interface CastModifier {
    public function modify(ArrayIterator $queue, Closure $chain, BaseDto $dto): Closure;
}
```

Modifiers can:

- Slice the next N attributes using `$attr = CastTo::sliceNextAttributes($queue, $N)`
- Build a subchain with `CastTo::buildCasterChain($attr, $dto);`
- Wrap or conditionally apply transformations

---

## 🧭 Caster Naming Conventions

Core, adapter and project casters can't share the same namespace, which forces differing prefixes. I propose these conventions:

- For **core** attributes: **CastTo**\\\*
  E.g. `#[CastTo\Trimmed]`, `#[CastTo\Floating]`, etc.
- For **adapter**-specific casters: **Casts**\\To\*
  E.g. `#[Casts\ToCarbon]`, `#[Casts\ToModel(User::class)]`, etc.
- For **project**-specific casters: **Cast**\\To\*
  E.g. `#[Cast\ToFoo]`, `#[Cast\ToPostalCode]`, etc.

---

## ✅ Summary Table

| Method                    | Discoverability | Type Safety | DI Support | Recommended For              |
| ------------------------- | --------------- | ----------- | ---------- | ---------------------------- |
| `#[CastTo\SomeType]`      | ✅              | ✅          | ✅         | Most expressive and readable |
| `#[CastTo(Class::class)]` | ✅              | ✅          | ✅         | Reusable custom logic        |
| `#[CastTo('slug')]`       | ❌              | ❌          | ❌         | One-off transformations      |

---

## `args` vs `constructorArgs`

| Parameter         | Purpose                         | Applies To          |
| ----------------- | ------------------------------- | ------------------- |
| `args`            | Passed to the `cast()` method   | All casters         |
| `constructorArgs` | Passed to the class constructor | Class-based casters |

---

## 🧠 Order of Caster Resolution

1. If `class_exists($methodOrClass)`:
   - If `constructorArgs` provided: instantiate
   - If not: instantiate with no arguments
   - If required args missing: attempt container resolution (adapter-defined)
2. Method on the DTO class
3. Method on the `CastTo` class
4. Delegate to `CastTo::$customCasterResolver->resolve(...)` (if defined)
5. Throw `CastingException`

---

## 🔁 Caster Caching

Each `CastTo` attribute is resolved to a closure and cached.

- Class-based casters are memoized by class name + `serialize(constructorArgs)`
- Method-based casters are cached by method + `serialize(args)`
- `CastTo::getCasterMetadata()` lets you inspect cached casters
- `CastTo::clearCasterMetadata()` resets the cache
