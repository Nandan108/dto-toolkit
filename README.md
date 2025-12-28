# DTO Toolkit Core

![CI](https://github.com/nandan108/dto-toolkit/actions/workflows/ci.yml/badge.svg)
![Coverage](https://codecov.io/gh/nandan108/dto-toolkit/branch/main/graph/badge.svg)
![Style](https://img.shields.io/badge/style-php--cs--fixer-brightgreen)
![Packagist](https://img.shields.io/packagist/v/nandan108/dto-toolkit)

✅ Requires PHP 8.1–8.4

DTO Toolkit Core is a lightweight, framework-agnostic library for defining, transforming, and (soon) validating **Data Transfer Objects** (DTOs) in PHP.

It offers a clean, declarative API powered by attributes — handling normalization, casting, and output shaping — all without coupling to any specific framework.

Casters, modifiers, and validators are composed into a fully declarative transformation DSL, JIT-compiled into efficient processing chains at runtime.


## ✨ Features

- 🧱 Minimal and framework-agnostic
- 🏷️ Attribute-based processing system with flexible resolution
- 🎯 Optional validation and normalization layers
- 🔄 Easily transform between DTOs and entities/models
- 🧩 Designed to work with pluggable [framework adapters](#adapter-packages) (Laravel, Symfony, etc.)


## 📦 Installation

```bash
composer require nandan108/dto-toolkit
```

## 🚀 Quick Start

```php
use Nandan108\DtoToolkit\Core\{FullDto, CastTo};

// FullDto includes all standard traits (CreatesFromArray, NormalizesFromAttributes, ExportsToEntity)
class MyDto extends FullDto {
    #[CastTo\Trimmed()]
    public ?string $name = null;
}

// Build DTO from array
$dto = MyDto::newFromArray(['name' => '  Alice  ']);

// Transform into an entity
$entity = $dto->toEntity();
```

Use a framework adapter (e.g. Symfony or Laravel) to unlock request/response integration and validation support.

---

## 📦 Core Namespace

If you're not using a framework, start with:

- `Nandan108\DtoToolkit\Core\FullDto`
- `Nandan108\DtoToolkit\Core\CastTo`

These provide a convenient, framework-free entry point with all standard functionality included.

---

## 📚 Documentation

- [DTO Fundamentals](docs/DtoFundamentals.md) – what DTOs are, why they matter, and how to use them in modern PHP
- [Casting](docs/Casting.md) – how casting works and how to write your own
- [Attributes](docs/Attributes.md) - List of attributes
- [Lifecycle](docs/DtoLifecycle.md) – Understanding the lifecycle of a DTO
- [Lifecycle Hooks](docs/Hooks.md) – customize behavior with `postLoad()` and `preOutput()`
- [Toolkit Comparison](docs/Comparison.md) – see how this toolkit compares to other PHP DTO/mapping libraries
- [Processing in detail](docs/Processing.md) — Validating and Transforming Data Through Nodes
- [Built-In Core Casters](docs/BuiltInCasters.md) — Full list of available `CastTo\*` casters
- [Built-In Core Validators](docs/BuiltInValidators.md) — list of available `Validate\*` validators *(more are planned)*
- [Built-In Core Modifiers](docs/BuiltInModifiers.md) — Full list of available `Mod\*` chain modifiers
- [Dependency Injection](docs/DI.md)
- Writing Adapters *(planned)*

---

## 🧩 Adapter Packages<a id="adapter-packages"></a>

- Laravel Adapter: [`nandan108/dto-toolkit-laravel`](https://github.com/nandan108/dto-toolkit-laravel) *(planned)*
- Symfony Adapter: [`nandan108/dto-toolkit-symfony`](https://github.com/nandan108/dto-toolkit-symfony) *(planned)*

Adapters will provide support for:
- Framework-compatible error handling and translations, for both validators and casters
- `fromRequest()` for DTO hydration from HTTP requests
- `toEntity()` or `toModel()` adapter-specific hydration
- `toResponse()` generation
- DI for class-based casters resolution
- Graceful handling of validation and casting exceptions in HTTP contexts, with standardized API error responses

---

## 🧪 Testing & Quality

- 100% test coverage using PHPUnit
- Psalm level 3
- Code style enforced with [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer):
  - Based on the `@Symfony` rule set
  - Aligned `=>` for better readability
  - Disallows implicit loose comparisons (`==`, `!=`)
- Commit message style: conventional, with details if any

---

## 🤝 Contributing

Bug reports, ideas, and contributions welcome! This project aims to stay lean, clean, and focused. If you're building an adapter or extending the system, feel free to open a discussion or issue.

---

## 🧭 Design Philosophy

This toolkit is built on a simple idea: **do the most work with the least number of moving parts**.

It favors:
- **Declarative code** over procedural boilerplate
- **Clever, expressive syntax** without falling into obfuscation
- **Minimalism with power** — clean by default, extensible when needed
- **Framework-agnostic design** with optional adapters that integrate smoothly when needed
- **Zero magic** in the core — everything is traceable, explicit, and predictable
- **Opt-in magic** in adapters — for just the right touch of convenience when working with frameworks
- **Separation of concerns** and **composability** — inspired by the Unix philosophy of doing one thing well
- **Performance-conscious by design** — DTOs should feel lightweight and fast to use, even in large batch transformations.

Verbose code is a tax on your time. This toolkit aims to keep things sharp, concise, and purposeful — so you can focus on what matters.

---

MIT License © [nandan108](https://github.com/nandan108)
