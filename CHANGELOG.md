# Changelog

All notable changes to this project will be documented in this file.

---

## [0.8.0] - 2025-12-05

### Added
- Processing chains now support **validation nodes alongside casters**, enabling end-to-end processing flows (casting + validation) on DTO properties.
- Introduced **error-collection modes** (`ErrorMode` enum) and the `ProcessingErrorList` container.
  Processing can now continue after node failures and accumulate errors using four modes:
  - `FailFast` (default)
  - `CollectFailToInput`
  - `CollectFailToNull`
  - `CollectNone`
  These modes apply uniformly to inbound (`fromArray()`) and outbound (`toOutboundArray()`) processing.
- New exception layout with domain-specific namespaces (`Config\*`, `Process\*`, `Context\*`) and `ProcessingExceptionInterface` to standardize adapter-facing error contracts.
- Guard/Transform/Extraction exception builders now expose structured template parameters to support downstream **i18n** in adapters.
- Added `ProcessingErrorList` (core utility) for frameworks to convert collected errors into native structures such as Symfony `ConstraintViolationList` or Laravel `MessageBag`.

### Changed
- Processing pipeline refactored to use generic **processing nodes** (casters and validators) rather than casting-only nodes; `ValidateBase` inherits the same lifecycle, context, and error semantics as casters.
- Exception subsystem refactored to align with best practices: clear domain separation, consistent error codes/templates, and enriched debug payloads for adapter and tooling integrations.
- Inbound and outbound normalization now support structured error reporting via the new error-collection system.
- Documentation updated to reflect processing/validation unification and the new exception hierarchy.

### Removed
- Legacy exception classes (`CastingException`, old `ProcessingException`, `ValidationException`) and the old `Exception\ExtractionSyntaxError` / `Exception\LoadingException` namespaces.

### BREAKING
- Exception class names and namespaces have changed; applications catching old exceptions must update to the new `Config\*` / `Process\*` classes.
- Processing chain APIs now operate on generic processing nodes; custom extensions that assumed casting-only behavior must adapt to validator-aware semantics.
- DTO hydration behavior has changed for failure scenarios: inbound and outbound processing now respect `ErrorMode`, affecting how DTO properties are assigned, nullified, or omitted on failure.

---

## [0.7.0] - 2025-07-04

### Added
- Integrated [prop-path](https://packagist.org/packages/nandan108/prop-path) for structured extraction via `#[MapFrom]` and `#[CastTo\Extract]`
- Integrated [prop-access](https://packagist.org/packages/nandan108/prop-access) to handle entity getter/setter access cleanly
- New exception types:
  - `ExtractionSyntaxError` for invalid prop-path strings
  - `LoadingException` for inbound value extraction errors
  - `DtoToolkitException` interface to unify all internal exceptions

### Changed
- `MapFrom` now uses prop-path internally and supports accessing input/context/dto roots
- `Extract` caster rewritten to use compiled path logic from prop-path
- `fromEntity()` and `toEntity()` now use `PropAccess::getValueMap()` and `PropAccess::getSetterMapOrThrow()`
- Method renaming in `HasContextInterface`: `getContext()` → `contextGet()`, etc.
- Unit tests improved for edge case coverage and context handling

### Removed
- `CaseConverter` and `EntityAccessorHelper` utility classes (replaced by `prop-access`)

### BREAKING
- Context method renaming will require refactoring if your code uses `HasContextInterface` directly
- `MapFrom` path syntax now validated by prop-path; invalid strings will throw `ExtractionSyntaxError`


---

## [v0.6.0]

### ✨ New Features

- **Chain Modifiers:** Added new built-in modifiers:
  - `ApplyNextIf` and `SkipNextIf`: Conditionally apply or skip the next N casters based on a resolved condition.
  - `FailIf`: Throws a casting exception if a condition is met (can be used as a simple validator).
  - `FirstSuccess`: Tries the next N casters/subchains and returns the first successful result.
  - `Wrap`: Groups the next N casters into a subchain (for grouping/branching).
  - `NoOp`: No-op modifier, useful as a placeholder or for conditional chains.
  - `Collect`: Runs input through multiple parallel subchains and collects their outputs.
- **Mapping:** Introduced `MapFrom` attribute for inbound mapping of input fields to DTO properties.
- **Groups:** Added `WithDefaultGroups` class attribute to set default groups in context upon instantiation.

### 🛠 Improvements

- **CastTo\Extract:** Renamed from `JsonExtract` to `Extract`. Now supports extracting values from both arrays and objects (using getter maps), and no longer performs `json_decode`.
- **Param Resolver:** `UsesParamResolver` now supports:
  - Method name and extra param value as JSON (e.g. `<dto:method:{"foo":1}>`)
  - Context key checks with equality or regex comparison
- **Entity Accessors:** Extracted entity getter/setter logic into `EntityAccessorMap`.
- **Error Reporting:** Improved error messages in `CasterChain` when fewer nodes are available than requested.
- **Casters:** All casters that require no arguments now extend `CastBaseNoArgs`.
- **Public API:** Made `getCurrentPropName()` and `getCurrentDto()` public in `CastTo`.

### 📚 Documentation

- Expanded `BuiltInModifiers.md` to cover all new modifiers with usage examples.
- Updated `Mapping.md` for `MapFrom` attribute.
- Added/updated docs for `WithDefaultGroups` and group scoping.
- Minor clarifications and doc cleanups.

### 🧪 Testing

- Added/updated tests for new modifiers, mapping, group scoping, and param resolver features.

---

## [v0.5.0] - 2025-05-14

### ✨ Flexible Parameter Resolution System

- Introduced `UsesParamResolver`, a general-purpose mechanism for resolving caster parameters dynamically from:
  - Literal values (e.g. `'fr_CH'`)
  - DTO methods (`<dto.locale>` → `$dto->getLocale()`)
  - DTO context (`<context.locale>` → `$dto->getContext('locale')`)
  - Static callables (`MyClass::getLocale($value, $prop, $dto)`)
  - Optional fallback closures
- Added traits: `UsesLocaleResolver`, `UsesTimeZoneResolver` (both built on `UsesParamResolver`)
- New interface: `BootsOnDtoInterface` enables casters to initialize themselves per DTO via `bootOnDto()`
- Casters like `LocalizedNumber`, `LocalizedCurrency`, `LocalizedDateTime`, etc. now support dynamic resolution

> See the new `CraftingAttributeCasters.md` guide for instructions on authoring custom casters using this system.

---

### 🧱 Caster Chain Architecture Refactor

- Replaced the static `CasterChainBuilder` with a dynamic, recursive `CasterChain` class
- Chain elements are now composed of structured nodes:
  - `CasterMeta` (leaf nodes)
  - `CasterChain` (nested chains from modifiers)
- Chain modifiers (`PerItem`, `FailTo`, etc.) now return structured `CasterChain` objects
- New interface: `CasterChainNodeInterface` unifies chain node behavior
- Method `CasterChain::recursiveWalk()` enables inspection and conditional operations on full chain trees
- Caster instances are preserved for introspection, debugging, and `bootOnDto()` application

---

### 🧪 Built-in Casters

#### New casters
- `CastTo\DateTimeFromLocalized` – parses locale-formatted strings into `DateTimeImmutable`
- `CastTo\DateTimeString` – formats `DateTimeInterface` as string using pattern or enum
- `CastTo\LocalizedCurrency`, `LocalizedNumber`, `LocalizedDateTime` – format numbers/dates with dynamic locale

#### Casters updated or renamed for consistency
- `CastTo\Floating` ➔ now supports parsing localized numeric strings via `decimalPoint` arg
- `CastTo\Base64Encode` ➔ `CastTo\Base64`
- `CastTo\Base64Decode` ➔ `CastTo\FromBase64`
- `CastTo\ToJson` ➔ `CastTo\Json`

---

### 📚 Documentation Updates

- Major expansion of `BuiltInCasters.md` to cover all core and new casters with usage details
- New section on parameter resolution with syntax table and priority rules
- Added `CraftingAttributeCasters.md` with guidance for writing custom caster attributes

---

### 🧪 Testing & Validation

- New test coverage for:
  - Locale resolution fallback paths
  - Caster booting (`bootOnDto`)
  - Localized formatting (`Intl`-based casters)
  - Caster chain modifier structure and behavior

---


## [0.4.1] - 2025-05-04

- Added PHP 8.4 to test matrix and marked as officially supported
- CI now tests against PHP 8.4; cs-fixer runs only under PHP 8.2
- Updated composer.json to allow php ">=8.1 <8.5"

## [0.4.0] - 2025-05-04

### Added
- Trait `IsInjectable` to provide a reusable `inject()` mechanism for DTOs and casters
- Identifier casing casters: `CamelCase`, `PascalCase`, `KebabCase`, `SnakeCase`
- Support for `#[Inject]` attribute on DTO classes to resolve them via `ContainerBridge::get()`
- Fallback instantiation in `ContainerBridge::get()` for zero-argument constructors
- `ContainerBridge::register()` to bind classes, singleton instances, or factories (closures)
- `ContainerBridgeTest` covering all registration and fallback behaviors

### Changed
- Renamed `#[Injected]` attribute to `#[Inject]`
- `FullDto` now uses `IsInjectable`, thereby implementing `Injectable`
- `BaseDto::newInstance()` now centralizes instantiation for all static constructors:
  - Uses `ContainerBridge::get()` if the DTO is marked with `#[Inject]`
  - Falls back to `new static()` otherwise
  - Automatically calls `$dto->inject()` and `$dto->boot()` if applicable
- Static constructors like `fromArray()` and `fromEntity()` now delegate to `newInstance()`
- fromEntity() now looks for getters named 'get'.PascalCase($propName)
- toEntity() now looks for setters named 'set'.PascalCase($propName)
- CastTo/Join now throws if an array element is not stringable
- Casters now access current DTO context via `$this->currentDto` instead of receiving it via method argument

### Removed
- Removed `$dto` argument from `CasterInterface::cast()` (now accessed via internal context)

### Dev / DX
- Updated cs-fixer config to disallow lose == comparisons.

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
  - `#[CastTo\FromJson]` * needs documenting
  - `#[CastTo\JsonExtract]` * needs documenting
  - `#[CastTo\NumericString]`
  - `#[CastTo\Base64]` * needs documenting
  - `#[CastTo\Base64Decode]` * needs documenting
  - `#[CastTo\RegexSplit]` * needs documenting
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
  - contract `Contracts\CastModifierInterface` ➔ `Contracts\CasterChainNodeProducerInterface`
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
