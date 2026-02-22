# Changelog

All notable changes to this project will be documented in this file.

---
## [1.4.4] - 2026-02-22

### Changed

- Runtime dependencies were updated:
  - `nandan108/prop-path` `^0.4.0` -> `^0.4.1`
  - `nandan108/prop-access` `^0.6.0` -> `^0.7.0`
- Locale/timezone-aware casters that resolve parameters via `UsesParamResolver` now accept callable providers in constructor arguments (array callables and other callables).
  - Updated caster constructor signatures accordingly (`DateTime`, `DateTimeFromLocalized`, `LocalizedDateTime`, `LocalizedNumber`, `LocalizedCurrency`).
- Condition modifiers now accept closures for dynamic condition providers:
  - `Mod\ApplyNextIf`, `Mod\FailIf`, `Mod\SkipNextIf` now take `string|\Closure` conditions.
  - This keeps promoted-property typing compatible with PHP while still enabling callable condition providers on PHP 8.5+ attribute arguments.
- `README.md` quality note now reflects Psalm level 1 strictness.
- `BaseDto::new()` now accepts an optional prebuilt DTO instance (`?BaseDto $instance = null`) and prepares that instance (inject/boot lifecycle) instead of always creating a new one internally.

### Fixed

- `Mod\FailIf` error metadata now reports callable conditions as `'callable'` instead of attempting JSON-encoding closure values.
- Removed an unused trait import from `Mod\NoOp`.

### Docs

- Updated casting/modifier documentation to explicitly describe dynamic provider support and syntax:
  - array callables for resolver params
  - closure and first-class callable attribute arguments on PHP 8.5+
  - classic closure syntax requirement in attribute arguments (`static function (...) { ... }`)
- Clarified that no built-in validator currently uses `UsesParamResolver`.

### Tests

- Added coverage for resolver callables in locale-aware casters (including array callable providers).
- Added PHP 8.5-specific coverage for closure/first-class callable condition providers in `Mod\ApplyNextIf`.
- Added runtime capability probes so closure-in-attribute tests are skipped on runtimes that do not support that syntax.

---
## [1.4.3] - 2026-02-20

### Added

- New internal cache-key helper: `Support\CacheKeySerializer` (used by processing-node memoization).
- New token: `type.countable`.
- New/updated message templates:
  - `processing.guard.invalid_value.compare_to_extract`
  - `processing.guard.array_length.above_max`

### Changed

- Psalm now enforced at errorLevel="1" (maximum strictness) in `psalm.xml`.
- `Assert\EnumCase` now supports both backed and unit enums explicitly:
  - backed enums accept case instances or backing values,
  - unit enums accept case instances or case names.
- `Assert\Length` now accepts any countable input in addition to strings/arrays.
- `Assert\CompareTo` and `Assert\CompareToExtract` now validate operators at construction time via `Internal\ValueComparator::assertOperator()`.
- `CastTo\AsArray` now handles `\Traversable` inputs directly, preserving source-key precedence over supplemental props, with optional recursive normalization of nested DTOs.
- Outbound export internals were hardened:
  - explicit target-class existence validation during entity instantiation,
  - nested DTO normalization extracted into a dedicated helper,
  - outbound-property key handling unified for hydration/remapping paths.
- `BaseDto::getClassRef()` now rejects DTO classes with constructors requiring mandatory parameters.
- CI Psalm invocation now runs with `--no-cache --no-progress`, and `psalm.xml` was tightened (max error level with targeted test-directory suppressions).

### Fixed

- `Exporter` outbound array merge semantics were corrected in `OutboundProps::toArray()` so source/exported properties reliably take precedence over supplemental properties on key collisions.
- `Assert\CompareToExtract` now uses dedicated error code/template suffix (`guard.compare_to_extract` / `compare_to_extract`) and clearer default left-path rendering (`$value` when omitted).
- `Assert\EnumBackedValue` now reports non-scalar operand types via standard expected-type messaging (`string|int`) instead of relying on `TypeError` flow.
- Export-to-entity errors for missing target classes now emit explicit domain messaging (`Target entity class '...' does not exist.`).

### Tests

- Added `tests/Unit/Support/CacheKeySerializerTest.php`.
- Expanded validator coverage for enum handling, countable length checks, compare-to operator validation, and URL edge cases.
- Updated compare-to-extract assertion messaging expectations.

---
## [1.4.2] - 2026-02-15

### Added

- New API-surface audit tool: `scripts/phpdoc-api-surface-audit` (internal implementation under `dev-tools/phpdoc-api-surface-audit/`).
- New default audit config file: `.phpdoc-api-surface-audit.php`.
- New composer scripts:
  - `composer api-audit`
  - `composer api-audit-strict`
- New internal shared comparison helper: `Internal\ValueComparator`.

### Changed

- API intent annotations (`@api` / `@internal`) were normalized across audited API surfaces in `src/` (types and declared public methods).
- `Assert\CompareTo` and `Assert\CompareToExtract` now share comparison behavior via `Internal\ValueComparator`.
- Extraction-related nodes now use `EvaluationFailureDetails`.
  - `ExtractionException` now exposes typed evaluator failure details (`$failure`) and reports `failedPath` in message parameters (replacing legacy `ExtractContext` usage).
- Runtime dependencies were updated:
  - `nandan108/prop-path` `^0.3.0` -> `^0.4.0`
  - `nandan108/prop-access` `^0.5.0` -> `^0.6.0`

### Fixed
- Localized datetime casters resolved trait method conflicts explicitly via `insteadof` rules.
- Fixed regex casters/validator behavior:
  - `RegexReplace`/`RegexSplit` now validate patterns at construction time and throw `InvalidArgumentException` for invalid patterns.
  - Runtime `preg_*` failures (`null`/`false`) are now explicitly converted into domain exceptions with `preg_last_error_msg()` context.
  - `Assert\Regex` now reports regex engine failures as `regex.matching_failed` instead of treating them as regular no-match outcomes.

### Tests

- Added coverage for runtime regex engine failure paths in `RegexReplace`, `RegexSplit`, and `Assert\Regex`.
- Added coverage for clearing locale resolvers via `DefaultErrorMessageRenderer::setLocaleResolver(null)`.
- Expanded enum error-message tests to verify production mode does not leak fully-qualified enum class names.

### Internal

- `ProcessingContext` internal stack/path management helpers were explicitly marked `@internal`; bootstrap/runtime config methods remain public API.
- API audit is now enforced in CI.
- Local hooks were updated:
  - `.git-hooks/pre-commit` now supports optional `.git/hooks/pre-commit.local` extension.
  - `.git-hooks/pre-push` runs API audit, PHPUnit, and Psalm, and supports optional `.git/hooks/pre-push.local` extension.

### Upgrade Notes

- Run `composer update` (or `composer install` with updated lockfile) to pick up `prop-path`/`prop-access` version bumps.
- To enable repository hooks locally, run `./scripts/setup-hooks.sh`.
- If you consume extraction exceptions directly, review `ExtractionException` constructor/factory inputs and the updated failure metadata (`$failure`, `failedPath`).

---
## [1.4.1] - 2026-02-14

### Changed

- Composer setup now supports local composer overrides via `composer.local.json` using `wikimedia/composer-merge-plugin`.
- Added `wikimedia/composer-merge-plugin` to `require-dev`.
- Added merge-plugin configuration in `extra.merge-plugin` (include `composer.local.json`, recurse enabled, replacement disabled, duplicates not ignored).
- Added Composer `config.allow-plugins` entry to explicitly allow `wikimedia/composer-merge-plugin`.
- Added top-level `merge-plugin` configuration block matching the merge settings for compatibility with tooling expecting that key.

---
## [1.4.0] - 2026-02-10

### Added

- New error rendering contract: `ErrorMessageRendererInterface`.
- Default locale-aware renderer: `DefaultErrorMessageRenderer`.
- Built-in English catalogs:
  - `resources/i18n/en/messages.php`
  - `resources/i18n/en/tokens.php`
- Runtime i18n configuration APIs (locale override/resolver, catalog registration, language-default locale map, cache/runtime reset helpers).
- New i18n documentation: `docs/i18n.md`.

### Changed

- `ProcessingException` messages are now rendered via a pluggable renderer (custom renderer, container-resolved renderer, or default renderer).
- Error messages now resolve from locale catalogs with fallback behavior and token translation.
- Validator/caster messaging normalized toward translated human-readable output in tests/docs.
- `CompareTo` `!=` behavior now uses loose inequality (`!=`) consistently with PHP operator semantics.

### Removed

- `Assert\Equals` validator class removed (use `Assert\CompareTo` with `==` / `===` as appropriate).

### Breaking

- Public static property renamed:
  - `ProcessingException::$max_text_length` -> `ProcessingException::$maxTextLength`
- If your integration relied on raw template text from `Exception::getMessage()`, message text is now renderer output; use `getMessageTemplate()` for stable template keys.

---

## [1.3.0]

### Changed

- Invalid interface errors for casters and validators now throw `InvalidConfigException`
  instead of `TransformException::invalidInterface()`.
- `#[Mod\Any]` now requires at least two strategies; default `$count` is `2`.
- `ProcessingException::getPropertyPath()` can now include processing node/chain provenance traces
  (for example: `price{CastTo\Trimmed->Mod\PerItem}[0]{CastTo\Rounded}`).
- Public processing provenance metadata uses processing node names instead of raw
  implementation class names (including anonymous class internals), to avoid leaking
  internal class details in user-facing errors.
  - DTO method-based provenance uses the DTO processing node name contract
    (`DTO::method` in prod; in dev, class-derived names such as `UserDto::method`
    or `AnonymousDTO(file:line)::method`; configurable via `ProvidesProcessingNodeNameInterface`).
  - Attribute casters and validators may also declare a name via this interface, but by default their node name is derived from their class name as the last two namespace segments (for example `CastTo\Boolean`, `Assert\Range`).
- Processing trace inclusion is enabled by default in dev mode and can be configured via `ProcessingContext`.
- `BaseDto::clearAllCaches()` now clears DTO metadata (including cached DTO processing node names) and processing-node metadata caches.
- `GlobalContextStorage` has been renamed to `DefaultContextStorage`; `GlobalContextStorage`
  remains as a deprecated alias for backward compatibility.

### Error Contract Normalization

- Error codes were normalized across `src/Assert/*` and `src/CastTo/*`:
  validators now consistently use `guard.*` and casters use `transform.*`.
- Template suffixes were normalized from mixed ad-hoc names to consistent families
  (for example `invalid_value.*`, `expected.*`, and structured subkeys like
  `compare_to.datetime` and `json.type`).
- Expected-value metadata now uses type keys (for example `type.numeric`,
  `type.array`) instead of free-form expected strings.
- Public error metadata now avoids exposing internal class details where normalized
  node/type names are available.

### Added

- `ProcessingContext::setIncludeProcessingTraceInErrors()`, `includeProcessingTraceInErrors()`, `setDevMode()`, and `isDevMode()`.
- These `ProcessingContext` config setters are intended for boot-time use and enforce safe semantics: changing values clears DTO/node caches and is rejected during active processing.
- Optional adapter extension point `GlobalFrameAwareContextStorageInterface` to report globally active frames for concurrent runtimes (fibers/coroutines/tasks) when enforcing boot-time config changes.
- `ProcessingException` now exposes `getThrowerNodeName()`, and processing-node execution enriches thrown `ProcessingException` instances with the current node name when missing. This provides node-level failure origin even when full processing traces are disabled.
- New optional contract `ProvidesProcessingNodeNameInterface` allows custom caster/validator producers to override the node name used in traces and thrower-origin metadata.

### Fixed

- Docs: Replace `CastingException` references with `TransformException` in casting/caster docs and update debugging guidance.
- `Assert\IsInstanceOf` now validates configured class/interface existence up front,
  avoiding runtime reflection failures when generating error parameters.

### Breaking

- `ProcessingNodeInterface` now requires `getName(): string`. Custom node implementations must add this method.
- `ProcessingException` helpers no longer accept/pass `methodOrClass`:
  `reason()`, `expected()`, `GuardException::required()`, `GuardException::invalidValue()`,
  and `ExtractionException::extractFailed()` signatures were simplified accordingly.
  Error-message parameters no longer include `methodOrClass`; node origin should be read from
  `ProcessingException::getThrowerNodeName()`.
- `ProcessingException::expected()` now normalizes `params.expected` to a list shape
  (e.g. `["type.numeric"]`) instead of a scalar string. Consumers that deserialize
  `params.expected` as a string must be updated.

## [1.2.0] - 2026-01-28

### Added

- New validators: `Assert\Json`, `Assert\Ip`, `Assert\Bic`, `Assert\CardScheme`,
  `Assert\Currency`, `Assert\Luhn`, `Assert\Iban`, `Assert\Isbn`, `Assert\Issn`.
- New casters: `CastTo\Coalesce`, `CastTo\Pad`, `CastTo\CardScheme`.
- New chain modifier: `#[Mod\SkipIfMatch]` to short‑circuit subsequent nodes when
  the current value matches configured values.

### Changed

- `Assert\Contains` now supports case-insensitive string matching.
- Third‑party notice added to LICENSE for Symfony CardScheme list provenance.

## [1.1.0] - 2026-01-27

### Added

- `ConstructMode` enum to control outbound entity construction:
  default constructor, array-based constructor, or named-arguments constructor.
- Support for constructor-based entity instantiation in outbound export
  (DTO/array → entity).
- `#[DefaultOutboundEntity]` now supports explicit construction mode selection.
- Automatic `#[MapTo]` name remapping when exporting via constructor arguments
  (array or named-args modes).

### Changed

- Outbound export pipeline refactored into explicit **normalize → prepare → hydrate**
  phases, clarifying control flow and constructor hydration semantics.
- `preOutput()` is now always executed for entity exports, including entities
  fully hydrated via constructor.
- Resolution of `#[DefaultOutboundEntity]` attributes has been moved into the
  attribute class itself via `DefaultOutboundEntity::resolveForDto()`,
  improving separation of concerns and reducing `BaseDto` coupling.

### Breaking (minor)

- `exportToEntity()` / `Exporter::export()` now use the `$supplementalProps`
  parameter (renamed from `$extraProps`) to better reflect additive,
  non-overriding semantics.
- `#[DefaultOutboundEntity]` second argument is `construct`, and `groups` is now in third position.

## [1.0.0] - 2026-01-23

### Added

#### Unified processing context

* Introduced a processing-context stack (`ProcessingContext`, `ProcessingFrame`, `ContextStorageInterface`, `GlobalContextStorage`) providing consistent context, group scope, property paths, and error-template handling across nested and recursive processing.
* DTO processing now executes within an explicit execution context rather than relying on static state.
* Processing context storage is decoupled behind ContextStorageInterface, allowing adapters to provide execution-local storage (e.g. for fibers or other concurrent runtimes).

#### Nested DTO processing

* Recursive processing support for both inbound and outbound flows, allowing nested DTO structures to be processed in the same direction as the initiating operation.
* Context (including groups and custom context values) is forwarded across nested DTO processing.
* `CastTo\Dto` for declarative nested DTO construction from arrays or objects.

#### Outbound projection and export

* Introduced a unified internal exporter (`Internal\Exporter`) that centralizes array and entity projection logic, shared by imperative helpers and declarative casters, with consistent recursion and entity-resolution semantics.
* `CastTo\Entity` for declarative entity construction from DTO or array values, with optional recursion.
* `CastTo\ToArray` for declarative projection of objects or DTOs to arrays, with optional recursion.
* `#[DefaultOutboundEntity]` attribute to declare default outbound entity targets (optionally scoped by groups).
* `ExportsOutbound` trait providing `exportToEntity()` and `exportToArray()` helpers.
* `PreparesEntityInterface` for custom entity instantiation logic.
* `CreatesFromArrayOrEntityInterface` to standardize DTO creation from arrays or entities.

#### Error handling for nested DTOs

* `Assert\DtoHasNoErrors` to guard nested DTOs with accumulated processing errors.
* `InnerDtoErrorsException` to surface nested DTO error lists explicitly.
* `ProcessingErrorList::clear()` for explicit error-list lifecycle control.

---

### Changed

* Trait `ExportsToEntity` renamed to `ExportsOutbound`.

  * `toEntity()` replaced by `exportToEntity()`
  * `exportToArray()` added
* DTO processing internals now rely on `ProcessingContext` for current DTO resolution, property paths, context, and error-template overrides.
* `BaseDto::clear()` now clears processing errors by default (`$clearErrors = true`).
* `BaseDto::getErrorList()` no longer accepts a replacement list; use `setErrorList()` instead.

---

### Fixed

* Property-path tracking and error-template overrides are now scoped per processing frame instead of using global static state.
* Nested processing correctly preserves context in validators and casters that depend on the current DTO or property path.
* Export-to-entity now throws a configuration error when an explicit entity class does not exist.
* `FullDto` now implements `CreatesFromArrayOrEntityInterface`.

---

### Breaking

* Trait `ExportsToEntity` renamed to `ExportsOutbound`.
  * `ExportsOutbound::toEntity()` renamed to `exportToEntity()`
  * `ExportsOutbound::exportToEntity()` now uses `$supplementalProps` instead of `$context`
* `CreatesFromArrayInterface` removed in favor of `CreatesFromArrayOrEntityInterface`.
* Custom processing nodes must migrate from `ProcessingNodeBase` statics to `ProcessingContext` APIs.
* `BaseDto::getErrorList()` signature changed; callers must use `setErrorList()`.


## [0.10.0] - 2026-01-15

### Added

- New chain modifiers:
  - `#[Mod\Assert($count)]`: run the next `$count` subchains in isolation, bubbling any error while preserving the original value.
  - `#[Mod\ErrorTemplate]`: override error templates within wrapped subchain(s).
- `CastTo\Age` caster to compute time differences from ISO datetime strings in seconds/hours/days/years.
- Core Assert validators ported from Symfony-style constraints, including `CompareTo`, `CompareToExtract`, `Equals`, `Contains`, `ContainedIn`, `IsBlank`, `IsNull`, `IsType`, and enum/format helpers.
- Sequence matching support for string/iterable containment checks.

### Changed

- `FirstSuccess` modifier renamed to `Any`.
- Validator naming aligned to `Assert\*` conventions (e.g. `InArray` → `In`, `InstanceOfClass` → `IsInstanceOf`, negated validators now expressed via boolean flags).
- Documentation updates across runtime/concurrency, processing, and built-in validator/modifier references.

### Fixed

- Processing exceptions now use late static binding for correct exception types.
- Minor doc/test cleanups and removal of debug residue.

### BREAKING

- Renamed/removed validators:
  - `InArray` -> `In`
  - `InstanceOfClass` -> `IsInstanceOf`
  - `NotBlank` -> `IsBlank(false)`
  - `NotNull` -> `IsNull(false)`
  - `IsArray` -> `IsType('array')`
  - `IsFloat` -> `IsType('float')`
  - `IsInteger` -> `IsType('int')`
  - `IsNumeric` -> `IsType('numeric')`

## [0.9.0] - 2025-12-28

### Added

- Introduced `PresencePolicy` enum and `#[Presence]` attribute for DTO- and property-level control over how missing vs. `null` inputs mark properties as filled.
- `MapFrom` now accepts an optional `ThrowMode` (defaults to ::MISSING_KEY to allow distinguish missing input from explicit `null` input).

### Changed

- Validation namespace renamed from `Validate\*` to `Assert\*`; base classes renamed `ValidateBase` ➔ `ValidatorBase` (and `ValidateBaseNoArgs` ➔ `ValidatorBaseNoArgs`).
- Public DTO properties without default values now throw `InvalidConfigException` during metadata initialization.
- Public properties prefixed with `_` are treated as internal and are skipped for input/output and processing.
- Static constructors are now `newFrom*` / `newWith*` and delegate to `BaseDto::new()`; instance loaders are `load*` (e.g., `loadArray()`). This reduces magic and makes it easier to keep psalm happy without suppression.

### BREAKING

- Validation namespace rename: use `Assert\*` instead of `Validate\*`, and update imports accordingly. Base validator class names updated as noted above.
- Default presence behavior changed: `null` inputs now mark properties as filled unless explicitly overridden with `#[Presence(PresencePolicy::NullMeansMissing)]`, at dto or prop level.
- DTOs must declare defaults for all public I/O properties; missing defaults now fail fast.
- Public properties starting with `_` are ignored for hydration/processing/output; move any externally visible data to non-underscored properties.
- Legacy `from*` static factories have been renamed to `newFrom*`; instance `from*` loaders have been renamed to `load*`. Static `with*` factories are now `newWith*` (instance `with*` methods unchanged).

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
- `BaseDto::new()` now centralizes instantiation for all static constructors:
  - Uses `ContainerBridge::get()` if the DTO is marked with `#[Inject]`
  - Falls back to `new static()` otherwise
  - Automatically calls `$dto->inject()` and `$dto->boot()` if applicable
- Static constructors like `newFromArray()` and `newFromEntity()` now delegate to `new()`
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
  - `#[CastTo\FromJson]`
  - `#[CastTo\JsonExtract]`
  - `#[CastTo\NumericString]`
  - `#[CastTo\Base64]`
  - `#[CastTo\Base64Decode]`
  - `#[CastTo\RegexSplit]`
- **Magic method helpers**:
  - DTOs now support dynamic `from*` / `with*` method forwarding via `__call` and `__callStatic`
- **Extended DTO construction methods**:
  - Added `_fromEntity()` for DTO instantiation from object instances
    E.g. `MyDtoClass::newFromEntity($inputEntity)->...`
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
