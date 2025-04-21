# DTO Toolkit Backlog

## Product Backlog Items

- [039] Publish to GitHub and add CI
- [040] Add `#[MapFrom(string|array $fields)]`
- [020] Add `#[MapTo(...)]` Attribute [See details](Mapping.md)
- [043] Add support for scoping groups (cross-cutting concern) [See details](#PBI-043)
- [046] Add modifier `#[SkipIfValIn(mixed $values, $return=null)]`, allows short-circuitting following caster(s) by returning \$return if input === \$values or input in $values.
- [049] Add `#[Collect(count: N)]` and `#[CastTo\NoOp]`. Aggregate the result of the next N casts into an array.
  E.g.: `#[Collect(3), CastTo\NoOp, CastTo\Floating, CastTo\Rounded(2), CastTo\CurrencyVal('USD')]` => Returns: `[string, float, rounded float, CurrencyVal object]`
- [044] Add support for DTO transforms (`\$dto->toDto($otherDtoClass)`) [See details](#PBI-044)
- [034] Add support for logging failed casts in FailTo/FailNextTo. `CastTo::$castSoftFailureLogger = function (CastingException $e, $returnedVal)`
- [048] Add debug mode setting. When enabled, add casting stack tracking (push/pop) to enable logging full context when failing within a chain.
- [050] Add `#[LogCast($debugOnly = true)]` to also allow logging non-failing chains.
- [036] Add support for `#[AlwaysCast(fromVal:..., groups:...)]`. Forces casting unfilled props by providing a default value to cast when unfilled.
- [028] Add nested DTO support with `CastTo\Dto(class, groups: 'api')` (from array or object), recursive normalization and validation
- [045] Add support for validation [See details](#PBI-045)
- [051] Add modifier `#[ApplyIf(check, count=1, negate=false)]` (and suggar `#[ApplyIfNot]` )
  - E.g.: `#[ApplyIf('isAdmin', count(2))]` (or `#[ApplyIf]`?) would result in applying the next 2 casters only if one of the following returns truthy:
    - `$dto->isAdmin()` (if available), or
    - `$dto->isAdmin` (if property exists), or
    - `$context['isAdmin']` (would require fromArray() to take an aditional `$context` arg).
- [052] Add `CastTo\ArrayFromJson`
- [053] Add `CastTo\JsonPath($path)`
- [054] Add `CastTo\RegexReplace($needle, $haystack)`
- [055] Add `#[MapFromInternal(string|array $fields)]` to allow mapping from one or more internal properties rather than external input.
- [056] Add `#[DtoLifecycle($inboundGroups, $validate, $normalizeSeq, $outboundGroups)]` to allow static default lifecycle configuration, to be applied during one-liners like `$dto->fromRequest($req)->toEntity()`, and `static BaseDto::amendDefaultLifecycleGroups($inbound, $validate, $normalizeSeq, $outbound) to allow runtime config modification`;
`$normalizeSeq` should allow an array of string|array $groups, defining step(s) in a normalization sequence scoped to said groups.
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
    - `CastTo::getCastingClosureMap(BaseDto $dto, bool $outbound = false, array $groups = [])` should filter out caster attributes that declare `groups` not intersecting with the active group set
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

