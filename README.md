# DTO Toolkit Core

![CI](https://github.com/nandan108/dto-toolkit/actions/workflows/ci.yml/badge.svg)
![Coverage](https://codecov.io/gh/nandan108/dto-toolkit/branch/main/graph/badge.svg)
![Style](https://img.shields.io/badge/style-php--cs--fixer-brightgreen)
![Packagist](https://img.shields.io/packagist/v/nandan108/dto-toolkit)

✅ Requires PHP 8.1–8.5

DTO Toolkit Core is a lightweight, framework-agnostic library for **processing** Data Transfer Objects (DTOs) in PHP.
It provides a unified model for normalization, validation, transformation, and export — with first-class support for nested structures and context-aware execution.

The API is fully declarative and attribute-driven: casters, validators, and control-flow modifiers compose into explicit processing pipelines that are JIT-compiled into efficient runtime chains, without coupling to any specific framework.

DTO Toolkit treats data transformation as a lifecycle, not a side effect.

## ✨ Features

- 🧱 Conceptually lightweight, framework-agnostic core
- 🏷️ Attribute-based processing pipelines with explicit control flow
- 🎯 Optional validation and normalization layers
- 🔄 Easily transform between DTOs and entities/models
- 🧬 First-class support for nested DTO processing (inbound and outbound), with consistent context propagation
- 🧩 Designed to work with pluggable [framework adapters](#adapter-packages) (Laravel, Symfony, etc.)


## 📦 Installation

```bash
composer require nandan108/dto-toolkit
```

## 🚀 Quick Start

```php
use Nandan108\DtoToolkit\Core\{FullDto, CastTo};

// FullDto includes all standard traits (CreatesFromArrayOrEntity, ProcessesFromAttributes, ExportsOutbound)
class MyDto extends FullDto {
    #[CastTo\Trimmed()]
    public ?string $name = null;
}

final class MyEntity
{
    public ?string $name = null;
}

// Build DTO from array
$dto = MyDto::newFromArray(['name' => '  Alice  ']);

// Transform into an entity (optionally recursive)
$entity = $dto->exportToEntity(MyEntity::class, recursive: true);
```

Use a framework adapter (e.g. Symfony or Laravel) to unlock request/response integration and validation support.

---

## 📦 Core Namespace

If you're not using a framework, start with:

- `Nandan108\DtoToolkit\Core\FullDto`
- `Nandan108\DtoToolkit\Core\CastTo`
- `Nandan108\DtoToolkit\Core\Assert`

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
- [I18n](docs/i18n.md) — locale-aware casters and error-message translation setup
- Writing Adapters *(planned)*

---

## 🏃 Runtime & Concurrency

- Multi-threaded runtimes (`pthreads` / `parallel`) are not supported by the core and are not planned to be.
- Execution context storage is abstracted behind `ContextStorageInterface`, allowing adapters to provide execution-local storage (e.g. for fibers/coroutines/tasks).
- Fiber/event-loop runtimes (Swoole, RoadRunner, ReactPHP, etc.) are **not officially supported yet**.
- Adapter hooks exist, but full concurrent-runtime support still requires synchronization guarantees around cache warm-up paths, which are not implemented yet.

---

## 🔒 Immutability & Value Objects

DTO Toolkit is **not an immutable DTO library**.

DTOs in DTOT are **mutable by design** and act as **transformation builders**: they ingest raw input, apply validation and normalization, and produce clean, structured output. This mutability is what enables DTOT’s dynamic processing model (casting chains, modifiers, context-aware behavior, recursive imports/exports).

That said, DTOT **plays very well with immutable objects**.

While DTOs themselves are mutable, DTOT is designed to **export into immutable value objects or entities** via constructor-based instantiation. This allows you to use DTOT as a *builder* for immutable domain models (value objects), without compromising immutability where it matters.

👉 If you’re looking for *immutability at the DTO layer itself*, DTOT may not be the right fit.<br>
👉 If you want **powerful, declarative transformation pipelines to produce immutable domain objects**, DTOT is a strong match.

---

## 🧩 Adapter Packages<a id="adapter-packages"></a>

- Laravel Adapter: [`nandan108/dto-toolkit-laravel`](https://github.com/nandan108/dto-toolkit-laravel) *(coming soon)*
- Symfony Adapter: [`nandan108/dto-toolkit-symfony`](https://github.com/nandan108/dto-toolkit-symfony) *(planned)*

Adapters will provide support for:
- Framework-compatible error handling and translations, for both validators and casters
- `fromRequest()` for DTO hydration from HTTP requests
- `exportToEntity()` or `toModel()` adapter-specific hydration
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
  - Disallows implicit loose comparisons (`==`, `!=`) except in explicit operator-semantics helpers
- Commit message style: conventional, with details if any

### CI Checks

GitHub Actions (`.github/workflows/ci.yml`) runs:

- `phpunit` on PHP `8.1` to `8.5`
- `php-cs-fixer --dry-run` (on one matrix leg)
- `psalm` (on one matrix leg)
- `composer api-audit-strict` (on one matrix leg)

### Run Locally Before Push

```bash
composer test
composer psalm
composer cs-fix
composer api-audit-strict
```

Optional: install repository Git hooks (pre-commit + pre-push):

```bash
./scripts/setup-hooks.sh
```

Installed `pre-commit` runs staged-file PHP-CS-Fixer checks and then optional local extension hook `.git/hooks/pre-commit.local` if present.
Installed `pre-push` runs `composer api-audit-strict`, PHPUnit, Psalm, and then optional local extension hook `.git/hooks/pre-push.local` if present.

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
