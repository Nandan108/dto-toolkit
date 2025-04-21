
# DTO Toolkit Core

A lightweight, framework-agnostic toolkit for defining, transforming, and validating **Data Transfer Objects** (DTOs) in PHP.

This package provides a clean, declarative API for working with DTOs — including input normalization, output transformation, and attribute-based type casting — all without coupling to a specific framework.

## ✨ Features

- 🧱 Minimal and framework-agnostic
- 🏷️ Attribute-based casting system with flexible resolution
- 🎯 Optional validation and normalization layers
- 🔄 Easily transform between DTOs and entities/models
- 🧩 Designed to work with pluggable framework adapters (Laravel, Symfony, etc.)

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
$dto = MyDto::fromArray(['name' => '  Alice  ']);

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

## 🧩 Adapter Packages

- Laravel Adapter: [`nandan108/dto-toolkit-laravel`](https://github.com/nandan108/dto-toolkit-laravel) *(not started yet)*
- Symfony Adapter: [`nandan108/dto-toolkit-symfony`](https://github.com/nandan108/dto-toolkit-symfony) *(in progress)*

Adapters will provide support for:
- Validation using framework services
- `fromRequest()` DTO hydration
- `toEntity()` or `toModel()` adapter-specific hydration
- `toResponse()` generation
- DI for class-based casters resolution
- Graceful handling of validation and casting exceptions in HTTP contexts, with standardized API error responses

---

## 📚 Documentation

- [DTO Fundamentals](docs/DtoFundamentals.md) – what DTOs are, why they matter, and how to use them in modern PHP
- [Casting](docs/Casting.md) – how casting works and how to write your own
- [Lifecycle](docs/DtoLifecycle.md) – Understanding the lifecycle of a DTO
- [Lifecycle Hooks](docs/Hooks.md) – customize behavior with `postLoad()` and `preOutput()`
- [Toolkit Comparison](docs/Comparison.md) – see how this toolkit compares to other PHP DTO/mapping libraries
- [Core Casters & Modifiers](docs/BuiltInAttributes.md) - see the list of built-in caster and modifier attributes
- Validation *(coming soon)*
- Writing Adapters *(coming soon)*

---

## 🧪 Testing & Quality

- 100% test coverage using PHPUnit
- Static analysis via Psalm (`--show-info=true` clean)

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
