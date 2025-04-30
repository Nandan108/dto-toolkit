# Changelog

All notable changes to this project will be documented in this file.
---

## [Unreleased]

### Added
- Trait `IsInjectable` to provide a reusable `inject()` mechanism for DTOs and casters

### Changed
- Renamed `#[Injected]` attribute to `#[Inject]`
- `FullDto` now uses `IsInjectable`, thereby implementing `Injectable`
- `BaseDto` now automatically calls after instanciating a new DTO :
  - `$dto->inject()` for DTOs implementing Injectable interface
  - `$dto->boot()` for DTOs implementing Bootable interface

---

## [v0.3.0] - 2025-04-29

### 🚀 Added
- **Group-based casting and property scoping**:
  - `#[Groups(...)]` cast modifier to control casting steps based on active context groups
  - `#[PropGroups(...)]` attribute to include/exclude properties during inbound or outbound phases
- **Context-aware processing chains**:
  - Introduced `HasContext`, `UsesGroups` traits and `HasGroupsInterface`
  - Processing chains can now adapt based on active runtime context
- **New core casters**:
  - `#[CastTo\RegexReplace]`
  - `#[CastTo\RemoveDiacritics]`
  - `#[CastTo\FromJson]`
  - `#[CastTo\JsonExtract]`
  - `#[CastTo\NumericString]`
  - `#[CastTo\Base64Encode]`
  - `#[CastTo\Base64Decode]`
  - `#[CastTo\RegexSplit]`
- **Magic method helpers**:
  - DTOs now support dynamic `from*` / `with*` method forwarding via `__call` and `__callStatic`
- **Extended DTO construction methods**:
  - Added `_fromEntity()` for DTO instantiation from object instances
    E.g. `MyDtoClass::fromEntity($inputEntity)->...`
- **Improved test tools**:
  - Added `Tests\Traits\CanTestCasterClassesAndMethods` trait for easier caster testing

### 🛠️ Changed
- **Chain building mechanism**:
  - Introduced recursive `buildNextSubchain()` composition
- **Renamed**:
  - CastModifier ➔ ChainModifier (namespace, interface, and base class)
  - Casters `ArrayFromCsv` ➔ `Split`, `CsvFromArray` ➔ `Join`
  - namespace `Attribute\CastModifier` ➔ `Attribute\ChainModifier`
  - contract `Contracts\CastModifierInterface` ➔ `Contracts\ChainModifierInterface`
- **Merged**:
  - `NormalizesInboundInterface` and `NormalizedOutboundInterface` into `NormalizesInterface`
- **Renamed**:
  - `CreatesFromArray` trait ➔ `CreatesFromArrayOrEntity`

### 📚 Documentation
- Updated `README.md` intro and structure
- Updated `docs/Casting.md` to reflect processing chains and outbound separation via `#[Outbound]`
- Added `docs/BuiltInCasters.md`
- Added `docs/BuiltInModifiers.md`
- Updated backlog with future ideas

### 🧹 Cleanups
- Tightened type declarations and internal structure
- Enabled parallel execution in php-cs-fixer
- `psalm` static analysis: fully green



---

## [v0.2.3] - 2025-04-23

### 🚀 Added
- `#[Outbound]` attribute to mark subsequent attributes as outbound-only, replacing repetitive `outbound: true` flags in `#[CastTo]`
- `#[DtoLifecycleConfig(...)]` attribute to declare default lifecycle group sequences for one-liner transforms like `$dto = MyDto::fromRequest($request)->toEntity()`
- `BaseDto::amendDefaultLifecycleGroups(...)` method to override lifecycle config at runtime

### 🔧 Changed
- Refactored `CastTo::getCaster()` to unify resolution and memoization for all caster types
- Moved injection and boot logic into `memoizeCaster()` to ensure consistent setup for class and method casters
- Refactored all tests to support the new `#[Outbound]` mechanism and improved group handling

### 🧪 Coverage
- Code coverage now at **98.25%**
  - Remaining uncovered lines are Xdebug edge cases and an `extension_loaded('intl')` fallback

## [v0.2.2] - 2025-04-22

### Added
- New **caster modifiers**:
  - `#[FailTo(fallback, handler)]` wraps upstream casters in try/catch
  - `#[FailNextTo(fallback)]` wraps only the next caster in the chain
  - Both support graceful fallback or custom exception handling
- New **conditional casters**:
  - `#[CastTo\IfNull($value)]` – replaces `null` with a default
  - `#[CastTo\NullIf($match)]` – replaces a matched value with `null`
  - `#[CastTo\ReplaceIf($when, $then)]` – replaces matched values with custom output
- New **unified integer caster**:
  - `#[CastTo\Integer(IntCastMode::*))]` supports `Ceil`, `Floor`, `Round`, `Trunc`
  - Replaces legacy `CastTo\Ceil` and `CastTo\Floor`
  - `IntCastMode` enum introduced to configure casting behavior

### Changed
- Refactored `CasterChainBuilder` into its own support class
- Updated `PerItem` modifier to use the new chain builder internally
- Core casters now **always throw** on invalid input (strict by default)
- Improved attribute resolution and modifier chaining logic
- Updated docs/DtoLifecycle.md
- Core\BaseDto: added `bool $runPreOutputHook=true` to `toOutboundArray()`, so `ExportsToEntity::toEntity()` can run `preOutput()` hook after entity hydration
- Improved `#[Injectable]` caster property injection mechanism

### Dev / DX
- Added `nunomaduro/collision` for improved CLI error feedback
- Added GitHub Actions CI (PHP 8.1–8.3, PHPUnit, Psalm)
- Added `php-cs-fixer` with PSR-12 + Symfony rules + tweaks (composer cs-fix)
- Added badges to `README.md` for CI status, coverage, and version
- Minor updates to `.gitignore` and `phpunit.xml.dist`
---

## [v0.2.1] - 2025-04-19

### Added
- Support for multiple `#[CastTo]` attributes per property (caster chaining)
- `CastModifier` interface for attributes that modify the behavior of casters in the chain
- New `#[PerItem]` modifier to apply the next N casters to each element of an array

### Changed
- Refactored `CastTo::getCastingClosureMap()` to support middleware-style caster chaining
  - Introduced `CastTo::buildCasterChain()` to compose casting pipelines
  - Introduced `CastTo::sliceNextAttributes()` to assist modifier behavior

🎉 This version lays the foundation for advanced casting scenarios with full chain control.

---

## [v0.2.0] - 2025-04-16

### Changed
- Refactored casting system to use Symfony-style dedicated attribute casters as the primary method
  - Introduced individual caster attributes under `CastTo\*` (e.g., `#[CastTo\Trimmed]`, `#[CastTo\Floating]`)
  - Removed support for the invalid syntax `#[CastTo::methodName(...)]`
  - Replaced legacy `CanCastBasicValues` trait with modular caster attribute classes
  - Retained support for method- and class-based casters using `#[CastTo('methodOrClass')]`
- Added `CastBase` base class to support shared logic and optional dependency injection via `#[Injected]`
- Introduced `CastingException` for structured error handling during cast operations

### Docs
- Added: `docs/DtoLifecycle.md` – explains the full lifecycle of a DTO (creation, validation, normalization, output)
- Updated: `docs/Casting.md` – clarified caster declaration styles and pros/cons
- Updated: `README.md` with revised feature list and new links
- Added: `docs/Mapping.md` documenting yet-unimplemented MapTo/MapFrom Attributes
- Added: `docs/Fundamentals.md` - explains the concept of DTO and use cases
- Added: `docs/Comparison.md` - compare the toolkit with other libraries in the PHP ecosystem
- Linked docs from README
- Added to README: Design philosophy updates (zero magic, opt-in magic)
- Backlog: Tidied PBI format, cleaned up and updated various items.

---

## [v0.1.0] - 2024-04-11

### Added
- Initial release of DTOT core
- `BaseDto` foundation and core traits:
  - `CreatesFromArray`
  - `ExportsToEntity`
  - `NormalizesFromAttributes`
- `CastTo` with dynamic and class-based casters
- `CanCastBasicValues` trait with static helpers
- `Core\FullDto` as batteries-included base class
- Lifecycle hooks: `postLoad()` and `preOutput()`
- Initial README.md and docs: Casting, Debugging, Project philosophy
