# DTO Toolkit Backlog

## Product Backlog Items

- [39] Publish to GitHub and add CI
- [37] Add support for chaining multiple #[CastTo] attributes (Attribute::IS_REPEATABLE)
- [19] Add support for #[InArray(N)]: flag indicating that next N (default=1) cast attributes
      operate on items within array \$value
- [43] Add support for scoping groups (cross-cutting concern) [See details](#PBI-43)
- [44] Add support for DTO transforms (`\$dto->toDto($otherDtoClass)`) [See details](#PBI-44)
- [31] Add strict support to #[CastTo] attribute
    > Add optional strict: bool flag to CastTo, enabling exceptions to be thrown on casting failure instead of silently returning null.
    - Requires updating CastTo to accept a strict param (or should it be a global setting set with
      CastTo::setGlobalStrictMode(true)?)
    - Modify normalizeInbound() logic to throw on failure if strict = true
    - Catch CastingExceptions and log them when CastTo::globalStrictMode === false and logging service available.
- [32] Add CastException class for strict mode casting errors
    - Include failed property name, caster name, and value
    - Used in normalization pipeline when casting fails and strict mode is enabled
- [33] Support casting chain failure propagation
    > Ensure that in a sequence of chained #[CastTo] attributes, failure in any step (with strict = true) aborts the pipeline and throws an exception.
    - Use repeatable attributes in declared order
    - If any cast step fails and strict = true, halt chain and throw CastException
- [34] Add support for logging failed casts in non-strict mode (optional DX feature)
    > Track failed cast attempts even when strict = false, for debugging purposes.
    - Introduce optional $castErrors array on DTO (internal use)
    - Could be exposed for dev mode / unit testing
    - No exceptions thrown — silent + trackable
- [36] Add support for #[AlwaysCast(group:...)] attribute, including group scoping support
    > Allow casting to be applied even when a property is not marked as filled. Useful for normalizing default values or entity-derived input.
    - Modify normalizeInbound() to apply casting when filled[prop] || #[AlwaysCast] is true
    - Add tests covering: default values, unfilled fields, and interaction with normal casting
- [20] Add #[MapTo(...)] Attribute [See details](Mapping.md)
- [40] Add #[MapFrom(string|array)]
- [28] Add nested DTO support with recursive normalization + validation
- [45] Add support for validation [See details](#PBI-45)
---

## Completed PBIs

- [1] Scaffold package with composer.json and PSR-4 autoloading
- [2] Introduce BaseInputDto with validated() function
- [3] Introduce fromRequest() and mechanism to get input from Request
- [4] Improve fromRequest() to allow flexible declaration of input sources
- [5] Add toEntity() to instanciate an entity from DTO data
- [6] Add normalize() method for casting inbound data to DTO's types
- [7] Add normalizeToDto() and normalizeToEntity()
- [8] Refactor toEntity() to use normalizeToEntity() and getEntitySetterMap()
- [9] Refactor toArray() to accept prop list
- [10] Make getEntitySetterMap() validate context props
- [11] Introduce CastTo attribute and casting helpers
- [12] Implement default castToIntOrNull, castToStringOrNull, castToDateTimeOrNull
- [13] Implement entity setter map with reflection and fallback
- [14] Rename BaseInputDto to BaseDto
- [15] Extract trait NormalizesFromAttributes
- [16] Add support for #[CastTo(..., outbound: true)] on output DTOs
- [17] Allow parameterized casts (e.g. separator for CSV)
- [18] Implement unit tests for existing features (and add testing to DoD)
- [24] Refactor toEntity() to base it on toOutputArray() + getEntitySetterMap() + using setters
- [25] Extract trait + interface for ValidatesInput
- [26] Extract trait + interface for CreatesFromArray
- [27] Extract trait CreatesFromRequest (uses CreatesFromArray)
- [30] Add support for CasterInterface
- [38] Split project in two packages: dto-toolkit and dto-toolkit/symfony
- [41] Add BaseDto::postLoad() and ::preOutput(array|object $entity) hooks
- [42] Add section on Caster DI in docs/Casting.md
- [23] Add toOutputArray() for array output with application of outbound casting

---

### <a id="PBI-43"></a>**🔧 PBI 43: Add support for scoping groups (cross-cutting concern)**

> “Scoping groups allow properties and their associated attributes (mapping, casting, validation) to selectively participate in a transformation context.”
- **Property selection and mapping**
    - Property: `#[Groups('api')]` makes attributed property invisible outside of 'api' scope.
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

### <a id="PBI-44"></a>🔄 PBI 44: Add support for DTO-to-DTO transformation

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

### <a id="PBI-45"></a> 🧩 PBI 45: Add framework-agnostic validation support with pluggable validator registry

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

