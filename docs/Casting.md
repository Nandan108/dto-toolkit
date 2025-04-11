
# Casters

Casters are a central feature of the DTO Toolkit. They allow you to modify the shape of:
- **Inbound data**, after validation, before hydrating a DTO
- **Outbound data**, before transforming a DTO to an entity or output

The goal of this project is to provide expressive, declarative syntax for writing lean DTOs and clean controller logic.

For example, after declaring a DTO class:

```php
class MyDto extends BaseDto implements NormalizesOutboundInterface {
    use NormalizesFromAttributes;

    #[CastTo::trimmed()]
    public ?string $name = null;
}

$entity = MyDto::fromArray([...])->toEntity();
```

And once the Laravel adapter is available, you could write a controller like this:

```php
public function save(Request $request): Response
{
    // Create model from request data
    $model = MyInputDto::fromRequest($request)->toModel()->save();

    // Prepare response using output DTO
    return response()->json(
        MyOutputDto::fromModel($model)->toResponse()
    );
}
```

---

## Declaring and Using Casters

The DTO Toolkit supports **three ways** to declare how a property should be cast. Each offers different trade-offs in reusability, expressiveness, and discoverability.

---

### 1. Method-Based Casters via String Identifier

```php
#[CastTo('slug', args: ['separator'])]
public string $slug;
```

This resolves to a method call (e.g., `castToSlug($value, ...$args)`) that can be defined:
- On the DTO class
- On the `CastTo` class
- Via an inherited trait

#### ✅ Pros
- Great for one-off or lightweight logic
- Keeps logic close to the DTO

#### ❌ Cons
- No type safety
- Not IDE-discoverable
- Easy to misname the method
- No dependency injection

---

### 2. Caster Classes (`CasterInterface`)

```php
#[CastTo(SlugCast::class, args: ['separator'], constructorArgs: [])]
public string $slug;
```

Casters must implement:

```php
interface CasterInterface
{
    public function cast(mixed $value, mixed ...$args): mixed;
}
```

If `constructorArgs` aren't provided and the caster requires them, the system attempts container resolution. This behavior is adapter-specific.

#### ✅ Pros
- Reusable and testable
- Supports dependency injection
- Clear separation of concerns

#### ❌ Cons
- More verbose
- Requires a class per transformation

#### 📦 Suggested namespaces for custom casters:
- `App\Dto\Casts\`
- `App\Dto\Casting\`

---

### 3. Static CastTo Constructors (Best DX)

This approach uses static factory methods on your `CastTo` subclass to define reusable attribute declarations:

```php
#[CastTo::slug('separator')]
public string $slug;
```

In your `CastTo` subclass:

```php
public static function slug(string $sep): static
{
    return new static('slug', [$sep]);
    // or
    return new static(MySlugCaster::class, args: [$sep]);
}

public function castToSlug($value, $sep): string {
    return Str::slug($value, $sep);
}
```

#### ✅ Pros
- IDE-discoverable
- Type-safe and expressive
- Great for adapter packages (Laravel/Symfony)

#### ❌ Cons
- Requires a `CastTo` subclass per adapter or domain

#### Suggested usage:
- In a Laravel adapter: `Nandan108\DtoToolkit\Laravel\Attribute\CastTo`
- In a Symfony adapter: `Nandan108\DtoToolkit\Symfony\Attribute\CastTo`

---

## Summary Table

| Method                         | Discoverability | Type Safety | DI Support | Recommended For |
|--------------------------------|-----------------|-------------|------------|------------------|
| `#[CastTo('type', ...)]`       | ❌              | ❌          | ❌         | One-shot DTO-local casters |
| `#[CastTo(TypeCast::class)]`   | ✅              | ✅          | ✅         | Reusable transformations |
| `#[CastTo::type(...)]`         | ✅              | ✅          | ❌/✅       | Best DX, adapters, helper methods |

---

## `args` vs `constructorArgs`

| Parameter         | Purpose                                | Applies To           |
|------------------|----------------------------------------|----------------------|
| `args`           | Passed to the `cast()` method           | All casters          |
| `constructorArgs`| Passed to the class constructor         | Class-based casters  |

---

## Caster Resolution Order

Resolution occurs in this order:

1. `class_exists($caster)`?
    - If constructorArgs are provided: instantiate
    - If no constructorArgs needed: instantiate
    - If required but missing: call `resolveWithContainer($class)` (adapter-defined)
2. Method on the DTO class
3. Method on the CastTo class
4. Delegate to `CastTo::$customCasterResolver->resolve($castName)` if defined
5. Throw `CastingException`

---

## Caster Caching

Each `CastTo` attribute is resolved to a **closure** that’s cached with metadata for inspection:

- Class-based casters are memoized by class name (single instance kept in memory)
    - ⚠️ `constructorArgs` are *not* part of the cache key — they should only be used with DI
    - Logic-altering arguments must be passed via `args`
- Method-based casters are cached by method name + `serialize(args)`
- Use `CastTo::getCasterMetadata(?string $methodKey = null)` to inspect cache
- Use `CastTo::clearCasterMetadata()` to reset
