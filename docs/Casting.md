# 📚 Casting: Transforming Data Through Processing Chains

DTO Toolkit uses **processing chains** to transform property values during inbound (normalization) and outbound (export) phases.

Processing chains are built from **casters** (CastTo\*), **modifiers** (Mod\*), and (soon) **validators**. Each component is declared as an attribute, and the chains are JIT-compiled for maximum efficiency.

Properties may have both inbound and outbound chains. To separate them, use the `#[Outbound]` attribute:
- **Inbound casters** are all casters that appear before an **`#[Outbound]`** attribute
  They will be applied by a `->normalizeInbound()` step which is run immediately after the raw input is populated into the DTO, via a `fromArray()`, `fromEntity()`, or in adapter packages `fromRequest()` or `fromModel()`.
- **Outbound casters** are those that appear *after* an `#[Outbound]` attribute
  These are be applied by a `->normalizeOutbound()` step which is run as a transformation step on DTO data before it is returned by `toOutboundArray()` or used to populate object such as in `toEntity()`, and in adapters: `toResponse()` or `toModel()`.

For a full list of built-in casters and modifiers, see:
- [Built-In Casters](BuiltInCasters.md)
- [Built-In Modifiers](BuiltInModifiers.md)

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
   public ?string $myProp;
```

Note that that PHP Attribute parameters may only contain scalars, arrays, constants, and constant expressions. Anything else is considered invalid by the PHP parser.

---

### 2. 🛠️ Custom Caster Classes (`CasterInterface`)
Define reusable casting logic in your own class.

In some cases, the built-in casters may be insufficient, even composed together, to transform or sanitize exactly as you wish. In such cases you may define your own casters, to be used either
- As an attribute (see previous syntax), by extending `Core\CastBase` and annotating your class with `#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]` , or
- By passing your classe's name to CastTo(), in which case it only needs to implement `Contracts\CasterInterface`.


```php
// caster definition
class MyCustomCaster implements CasterInterface {
    public function cast(mixed $value, array $args): mixed {
        // custom logic here
    }
}
```

```php
// caster usage
#[CastTo(MyCustomCaster::class, args: ['param'], constructorArgs: [])]
public string $value;
```


If a caster class requires constructor arguments, but these are not provided in the Attribute's `constructorArgs`, the system will attempt container-based resolution (adapter-defined).

For more details about dependency injection, see [DI with ContainerBridge](DI.md).

---

### 3. ⚙️ Method-Based Caster (String Identifier)

Lightweight syntax that calls a method on the DTO.

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

You can apply multiple caster attributes to a single property.
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

Modifier attributes implement the `ChainModifier` interface and control the behavior of the **caster chain**.

You can place modifiers before or after casters depending on their function:

---

#### 📚 Example: `#[PerItem]`

Apply the next N casters to each item in an array.

```php
#[CastTo\Split]
#[Mod\PerItem(2), CastTo\Floating, CastTo\Rounded(2)]
#[CastTo\Join(';')]
public string $prices = '10,12.45533,0';
```

This:

1. Parses the CSV into an array
2. Applies float conversion + rounding to each item
3. Implodes the array back to CSV with `;`

See [Built-In Modifiers](BuiltInModifiers.md) for more examples.

---

## 🧭 Naming Conventions

#### Casters
Core, adapter and project casters can't share the same namespace, which forces differing prefixes. I propose these conventions:

- For **core** attributes: **CastTo**\\\*
  E.g. `#[CastTo\Trimmed]`, `#[CastTo\Floating]`, etc.
- For **adapter**-specific casters: **Casts**\\To\*
  E.g. `#[Casts\ToCarbon]`, `#[Casts\ToModel(User::class)]`, etc.
- For **project**-specific casters: **Cast**\\To\*
  E.g. `#[Cast\ToFoo]`, `#[Cast\ToPostalCode]`, etc.

#### Modifiers

Modifiers on the other hand, are like flow control primitives that can be generally considered a complete set. Thus, there won't be adapter-specific ones, and there should be no need of developping your own. Importing them individually is fine, but Importing their namespace as Mod makes their usage clearer.

```php
use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;
...

class MyDto extends BaseDto {
    #[CastTo\Integer(IntCastMode::Ceil)]
    #[Mod\Failto(null)]
    public int|string|null $myProp;
}
```

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
3. Delegate to `CastTo::$customCasterResolver->resolve(...)` (if defined)
4. Throw `CastingException`

---

## 🔁 Caster Caching and Instance Lifecycle

Casting attributes are resolved to a closure and **internally cached** for optimal performance.

- **Class-based casters** (those implementing `CasterInterface`) are memoized by:
  - Class name + `json_encode(constructorArgs)`
- **Method-based casters** (e.g. `#[CastTo('slug')]`) are cached by:
  - Method name + `json_encode(args)`

Caching allows repeated transformations across DTO instances without repeated instantiations or resolution overhead.

After being compiled, transformation chains are also memoized, per-dto class and group scope.

---

### 🧠 Stateless by Default

The system is optimized around the idea that **caster classes are stateless**, or only rely on **injected services**. This means:

- A single instance per caster class can be safely reused
- No duplicated setup or configuration cost per transformation
- Casters remain lightweight and performant by design

---

### 🧪 When Statefulness Is Needed

If a caster truly needs internal state (e.g. holding temporary config, registering with another service), you still have options:

- Delegate state to an **injected service**, and inject it via `#[Injected]`
- Implement the optional `Bootable` interface and use the `boot()` method for additional setup after service injection
- For quick-and-dirty one-offs, define a caster via a DTO method and use a `static` variable for state:
  ```php
  #[CastTo('someCustom')]
  public string $field;

  public static function castToSomeCustom($value) {
      static $cache = [];
      ...
  }
  ```

These paths are opt-in and fully compatible with the core memoization logic.

---

### 🧰 Tools for Debugging

- Use `CastTo::getCasterMetadata()` to inspect resolved and cached caster instances
- Use `CastTo::clearCasterMetadata()` to reset all cached instances

*A debug mode is planned, where CastingExceptions will provide full chain context information to the catcher when thrown from within a chain.*
