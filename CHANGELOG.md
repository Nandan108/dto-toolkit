# Changelog

All notable changes to this project will be documented in this file.

---

## [UNRELEASED]

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
  - Removed `CastTo\Ceil` and `CastTo\Floor` in favor of this consolidated approach
  - Added `IntCastMode` enum to support integer casting strategies

### Changed
- Refactored `CasterChainBuilder` into its own support class
- Updated `PerItem` modifier to use the new chain builder internally
- All core casters now always throw on invalid input
- Improved attribute resolution and chaining logic

### Dev / DX
- Added `nunomaduro/collision` for improved CLI error reporting
- Minor `.gitignore` and `phpunit.xml.dist` updates

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
