# 🧩 Dependency Injection in DTO Toolkit

DTO Toolkit supports lightweight, flexible dependency injection for DTOs and Casters — with no framework required. You can inject dependencies either into properties or via constructor, using a single declarative attribute: `#[Inject]`.

These features are optional. If you don’t register a container, DTOs and casters will continue to work as usual using simple `new` instantiation.

---

## 🏷️ Class-Level Injection for DTOs

If your DTO class is marked with `#[Inject]`, it will be resolved from the container (via `ContainerBridge::get(static::class)`) instead of using `new static()`.

```php
use Nandan108\DtoToolkit\Attribute\Inject;

#[Inject]
class MyDto extends BaseDto
{
    public function __construct(private ServiceInterface $service) {}
}
```

This enables constructor injection for DTOs that need context-aware services like a locale resolver, permission checker, or request-bound user.

---

## 🧷 Property Injection via `#[Inject]` (DTOs and Casters)

You can also mark typed properties with `#[Inject]`. These will be populated by calling `$dto->inject()` or `$caster->inject()` on classes that use the `IsInjectable` trait.

```php
class SomeCaster extends CastBase
{
    use IsInjectable;

    #[Inject]
    private SomeService $service;

    public function __construct()
    {
        $this->inject();
    }
}
```

This is especially useful for caster Attributes, which are instantiated via reflection and can't receive constructor arguments.

---

## 📦 Centralized DTO Instantiation with `newInstance()`

DTOs extending `BaseDto` can use the static method `newInstance()` to create a fresh instance of themselves. This method automatically handles:

- Resolving the instance
    - via `ContainerBridge::get()` if the class is marked with `#[Inject]`
    - Falling back to `new static()` if not
- Calling `$dto->inject()` for DTOs that implement `Injectable`
- Calling `$dto->boot()` for DTOs that implement `Bootable`

```php
#[Inject]
class MyDto extends BaseDto
{
    public function __construct(private LocaleContext $context) {}
}

$dto = MyDto::newInstance();
```

You usually won’t need to call `newInstance()` directly — it’s used internally by `fromArray()`, `fromEntity()`, and other `from*()` or `with*()` methods via `__callStatic()`.

---

## 🧰 Manual Configuration with ContainerBridge

The class `ContainerBridge` is the central entry point for dependency injection in DTO Toolkit. It supports:

- Setting a PSR-11 container (`setContainer($container)`)
- Registering fallback bindings manually
- Returning bound singletons, results from closures, or autoinstantiating zero-arg classes

```php
// will auto-instanciate SomeSerivce (zero-arg constructor)
ContainerBridge::register(abstract: ServiceInterface::class, concrete: SomeService::class);
// register an OtherService singleton
ContainerBridge::register(abstract: OtherService::class, concrete: new OtherService());
// register a factory Closure (new MyService intance returned each time)
ContainerBridge::register(abstract: MyService::class, concrete: fn() => new MyService());
```

If no container has been configured and no binding was registered, `ContainerBridge::get(SomeClass::class)` will attempt to instantiate `SomeClass` via reflection — but only if the constructor requires no arguments.

---

## 🔄 Integration with Framework Containers

If you’re using Symfony, Laravel, or another PSR-11-compatible container:

```php
ContainerBridge::setContainer(ContainerInterface $container);
```

From that point forward, DTOs and casters will be able to pull dependencies from the shared application container — while still allowing you to override or register test bindings manually.
*This, however, will be wired up by default in upcoming adapter packages.*
