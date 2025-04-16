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
#[CastTo\DateTime(outbound: true)]
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
For casters that take one argument that affects casting logic in a clear way, it is permissible to provide a value using the positional syntax (see Slug and Rounded examples above). In other situations (more arguments or outbound: true) using the named argument syntax is advised for clarity.
```php
   #[CastTo\Rounded(2, outbound:true)]
   public float|string|null $price;
```

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

## 🧭 Caster Naming Conventions

To distinguish core vs. project-specific casters:

- Use `#[CastTo\Trimmed]`, `#[CastTo\FloatType]`, etc. for **core attributes**
- Use `#[Cast\ToSlug]`, `#[Cast\ToPostalCode]`, etc. for **project- or adapter-specific casters**

This convention mirrors Symfony's Validator system (`Assert\NotBlank`, etc.).

---

## ✅ Summary Table

| Method                          | Discoverability | Type Safety | DI Support | Recommended For               |
|---------------------------------|-----------------|-------------|------------|-------------------------------|
| `#[CastTo\SomeType]`           | ✅              | ✅          | ✅         | Most expressive and readable |
| `#[CastTo(Class::class)]`       | ✅              | ✅          | ✅         | Reusable custom logic         |
| `#[CastTo('slug')]`             | ❌              | ❌          | ❌         | One-off transformations       |

---

## `args` vs `constructorArgs`

| Parameter         | Purpose                                | Applies To           |
|------------------|----------------------------------------|----------------------|
| `args`           | Passed to the `cast()` method           | All casters          |
| `constructorArgs`| Passed to the class constructor         | Class-based casters  |

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
