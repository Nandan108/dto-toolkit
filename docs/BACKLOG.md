# DTO Toolkit Backlog

## Product Backlog Items

- **[053]** Add Caster LocalizedNumber, with trait UsesLocaleResolver. See if we can share resolution/injection logic with CastTo
- **[069]** Add  #[WithDefaultGroups(...)] class attribute, takes same params as UsesGrops::_withGroups() and auto-applies them after instanciation
- **[046]** Add modifier `#[SkipIfMatch(mixed $values, $return=null)]`, allows short-circuitting following caster(s) by returning \$return if input === \$values or input in $values.
- **[049]** Add `#[Collect(count: N)]` and #[Wrap(N)] (`#[NoOp]` = sugar for #[Wrap(0)]). Aggregate the result of the next N subchains into an array.
  E.g.: `#[Collect(3), NoOp, Wrap(2), CastTo\Rounded(1), CastTo\NumericString(2,','), CastTo\CurrencyVal('USD')]` => cast("2.42") Returns: `["2.42", "2,40", CurrencyVal object]`
- **[059]** Add #[Wrap(N)] chain modifier that does nothing but wraps a subchain. Could be useful with #[Collect(N)]
- **[051]** Add modifier `#[ApplyIfTruthy(check, count=1, negate=false)]` (and suggar `#[ApplyIfNot]` )
  - E.g.: `#[ApplyIfTruthy('isAdmin', count(2))]` (or `#[ApplyIf]`?) would result in applying the next 2 casters only if one of the following returns truthy:
    - `$dto->isAdmin()` (if available), or
    - `$dto->isAdmin` (if property exists), or
    - `$context['isAdmin']` (would requires $dto instanceof HasContextInterface).
- **[040]** Add `#[MapFrom(string|array $fields)]`
- **[020]** Add `#[MapTo(...)]` Attribute [See details](Mapping.md)
- **[055]** Add `#[MapFromInternal(string|array $fields)]` to allow mapping from one or more internal properties or values already cast in a previous step, rather than external input.
- **[044]** Add support for DTO transforms (`\$dto->toDto($otherDtoClass)`) [See details](#PBI-044)
- **[034]** Add support for logging failed casts in FailTo/FailNextTo. `CastTo::$castSoftFailureLogger = function (CastingException $e, $returnedVal)`
- **[062]** Add support for getCaster() debug mode
  - When enabled, caster closures push/pop debug context during execution
  - On casting failure, CastingException::castingFailure() can include full chain trace
  - Example message: "PropName: Caster1->Caster2->FailNextTo(PerItem(Caster3 -> Caster4))"
- **[048]** Add debug mode setting. When enabled, add casting stack tracking (push/pop) to enable logging full context when failing within a chain.
- **[050]** Add `#[LogCast($debugOnly = true)]` to also allow logging non-failing chains.
- **[036]** Add support for `#[AlwaysCast(fromVal:..., groups:...)]`. Forces casting unfilled props by providing a default value to cast when unfilled.
- **[028]** Add nested DTO support with `CastTo\Dto(class, groups: 'api')` (from array or object), recursive normalization and validation
- **[045]** Add support for validation [See details](#PBI-045)
  The mapping source will be different for inbound and outbound casting, this needs reflexion.
- **[056]** Support multi-step casting by making withGroups(inboundCast: ...) take a sequence of group(s)
  Then apply each step in sequence. Same with outboundCast.
  **[058]** Add a doc about FullDto and making one's own slimmed-down version if not all features are needed
- **[063]** Add validation attributes as part of caster chains
  - E.g., #[Valid\Range(min, max)], #[Valid\StrLength()]
  - Non-mutating steps: must have validate($value): void, can throw ValidationException
  - Must be phase-aware like other processing chain elements
  - Consider renaming "casting chains" to "processing chains" where applicable
- **[064]** Add framework-specific ValidationException mappers in adapters
  - Core DTOT will throw a generic ValidationException on validation failure
  - Allow adapters (e.g., dtot-adapter-laravel, dtot-adapter-symfony) to map or wrap ValidationExceptions into framework-native exceptions
  - This enables seamless integration with Laravel and Symfony's validation error handling mechanisms
  - Keep validation logic and error reporting fully pluggable and framework-agnostic
  - Consider a "ValidationErrorMapperInterface" for adapter overrides (optional future enhancement)
- **[065]** Add FirstSuccess chain modifier: #[FirstSuccess($count)]
  - Wraps next $count suchains as candidates and attemps each one in sequence
  - Return the result of the first successful chain, or throw if none succeeds.
  - Enables graceful branching on multi-type or multi-format inputs
---

## Completed PBIs

- [001] Scaffold package with composer.json and PSR-4 autoloading
- [002] Introduce BaseInputDto with validated() function
- [003] Introduce fromRequest() and mechanism to get input from Request
- [004] Improve fromRequest() to allow flexible declaration of input sources
- [005] Add toEntity() to instanciate an entity from DTO data
- [006] Add normalize() method for casting inbound data to DTO's types
- [007] Add normalizeToDto() and normalizeToEntity()
- [008] Refactor toEntity() to use normalizeToEntity() and getEntitySetterMap()
- [009] Refactor toArray() to accept prop list
- [010] Make getEntitySetterMap() validate context props
- [011] Introduce CastTo attribute and casting helpers
- [012] Implement default castToIntOrNull, castToStringOrNull, castToDateTimeOrNull
- [013] Implement entity setter map with reflection and fallback
- [014] Rename BaseInputDto to BaseDto
- [015] Extract trait NormalizesFromAttributes
- [016] Add support for #[CastTo(..., outbound: true)] on output DTOs
- [017] Allow parameterized casts (e.g. separator for CSV)
- [018] Implement unit tests for existing features (and add testing to DoD)
- [024] Refactor toEntity() to base it on toOutputArray() + getEntitySetterMap() + using setters
- [025] Extract trait + interface for ValidatesInput
- [026] Extract trait + interface for CreatesFromArray
- [027] Extract trait CreatesFromRequest (uses CreatesFromArray)
- [030] Add support for CasterInterface
- [038] Split project in two packages: dto-toolkit and dto-toolkit/symfony
- [041] Add BaseDto::postLoad() and ::preOutput(array|object $entity) hooks
- [032] Add CastingException class for strict mode casting errors
- [042] Add section on Caster DI in docs/Casting.md
- [023] Add toOutputArray() for array output with application of outbound casting
- [037] Add support for chaining multiple #[CastTo] attributes (Attribute::IS_REPEATABLE)
- [019] Add support for #[PerItem(N)]: flag indicating that next N (default=1) cast attributes operate on items within array \$value
- [047] Add modifier `#[FailTo(mixed $fallbackValue=null, string|callable \$handler=null)]`, `#[FailNextTo]`
- [048] Add `#[CastTo\IfNull($output='')]`, `#[CastTo\NullIf(\$input='')]`, `#[CastTo\ReplaceIf(when, then)]`
- [031] ALWAYS STRICT: Update all casters to always throw if input value is invalid.
- [039] Publish to GitHub and add CI
- [057] Replace per-Caster $outbound ctor param with #[Outbound]: marks subsequent attributes as belonging to outbound phase
- [054] Add `CastTo\RegexReplace($needle, $haystack)`
- [066] Add BaseDto::fromEntity($object)
- [052] Add Casters fromJson, JsonExtract, NumericString, Base64Encode/Decode, RegexSplit
- [060] Extract inject() functionality from caster to IsInjectable trait, rename #[Injected] to #[Inject]
- [061] let DtoBase use IsInjectable, make sure $dto->inject() is called after new instanciation
- [068] Add casters to convert input to camelCase, kebab-case, PascalCase and snake_case
- [067] Fix to/fromEntity getEntityGetters() and setEntitySetters() to also check for camelCase methods for snake_cased properties

---

### <a id="PBI-043"></a>**🔧 PBI 43: Add support for scoping groups (cross-cutting concern)**

> “Scoping groups allow properties and their associated attributes (mapping, casting, validation) to selectively participate in a transformation context.”
- **Property selection and mapping**
    - Property: `#[PropGroups('api')]` makes attributed property invisible outside of 'api' scope.
    - Modifier: `#[Groups('api', 4)]` adds 'api' group scoping to the next 4 chained casters
    - Mapping: `#[MapFrom(..., groups: [...])]` and `#[MapTo(..., groups: [...])]`
    - `BaseDto::toArray(array $props = [], array $groups = [])` should filter properties based on group matching
    - Downstream consumers (`fromArray`, `fromDto`, `toOutboundArray`, `toEntity`) must accept and pass `?array $groups` to `toArray`
- **Casting**
    - `CastTo::getCastingClosureMap(BaseDto $dto, array $groups = [])` should filter out caster attributes that declare `groups` not intersecting with the active group set
    - `NormalizesFromAttributes` must pass `groups` into casting closure map resolution
- **Caster syntax**: Caster attributes should allow optional `groups: string|array`
    - `#[CastTo\Slug(groups: ['public', 'seo'])]`
- **Future-compatible**
    - Architecture should anticipate applying scoping groups to validation (e.g. `#[Assert\NotBlank(groups: ['api'])]`)

### <a id="PBI-044"></a>🔄 PBI 44: Add support for DTO-to-DTO transformation

> “DTO transforms allow one DTO to be converted into another through a structured, group-aware data handoff.”
- **Example use case**: `$apiDto = $internalDto->toDto(ApiUserDto::class, groups: ['api']);`
- **Public API**
  - Add `BaseDto::toDto(string $targetDtoClass, ?array $groups = null): static`
    - Default implementation:  `return (new $targetDtoClass)->fromDto($this, $groups);`
  - Add `BaseDto::fromDto(BaseDto $sourceDto, ?array $groups = null): static`
    - Default implementation:`return $this->fromArray($sourceDto->toOutboundArray(groups: $groups));`
- **Responsibility**
  - The **target DTO** is responsible for requesting+interpreting the source DTO’s output
  - This supports overriding `fromDto()` to customize data interpretation, grouping, or mapping logic

- **Optional override hooks**: Subclasses may override `fromDto()` to bypass outbound casting, apply custom property mapping or normalize derived data.

---

### <a id="PBI-045"></a> 🧩 PBI 45: Add framework-agnostic validation support with pluggable validator registry

> “Allow DTOs to be validated in a clean, explicit, framework-neutral way, supporting one or more registered validation engines transparently.”

#### 🔹 Implementation concerns:

- **Define `ValidationInterface` in Core**
  - Contract:
    ```php
    public function validate(object $dto, ?array $groups = null): void;
    ```
  - May throw a shared `ValidationException` for any failure

- **Add a `HasValidatorRegistry` trait**
  - Provides static methods to register one or more validators:
    ```php
    BaseDto::setValidator('symfony', new SymfonyValidatorBridge());
    BaseDto::setValidator('laravel', new LaravelValidatorBridge());
    BaseDto::clearValidators(); // optional for testing
    ```

- **Integrate into `BaseDto`**
  - Add a `validate(?string $with = null): void` method
  - Behavior:
    - If `with` is provided, only that validator is used
    - If `with` is `null`, all registered validators are applied in order

- **Group support**
  - Call each registered validator with optional `string|array $groups = []`

- **Usage examples**
  ```php
  // run all registered validators, passing groups: ['api']
  $dto->validate(groups: 'api');
  // run only the Symfony one
  $dto->validate(groups: ['api', 'admin'], with: 'symfony');
  ```

- **Optional fallback validator**
  - Provide a `NullValidator` that implements `ValidationInterface` and always passes (useful in testing or CLI tools)

- **Adapter registration**
  - Symfony and Laravel adapters can auto-register their validator bridges during bootstrapping

- **Keeps core decoupled**
  - No dependency on Symfony, Laravel, or any validation engine

