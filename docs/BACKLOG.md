# DTO Toolkit Backlog

## Product Backlog Items

### TODO before v.1.0
- **[075]** Port a majority of basic Symfony validation constraints (`#[Assert\...]`) to DTOT Core
  - #[Assert\CompareTo(string $op, $scalar)] // $op in [==, ===, !=, !==, <, <=, >, >=]
  - #[Assert\CompareToExtract($op, $rightPath, $leftPath=null)] // use #[Extract] logic with propPath expressions `$path` extract from roots `['dto' => $dto, 'context' => $dto->context]`, `$leftPath` (optional): extracts from roots `[value, dto, context]`
  - #[Assert\Equals($value, $strict = true)] // same as CompareTo("===", $value)
  - #[Assert\IsType(string $type)] // See symfony constraints/Type. $type is one of: 'bool', 'boolean', 'int', 'integer', 'long', 'float', 'double', 'real', 'numeric', 'string', 'class-string', 'scalar', 'array', 'iterable', 'countable', 'callable', 'object', 'resource', 'null', [...$types].
  - #[Assert\IsNull(bool $expect = true)] // sugar for IsType('null')
  - #[Assert\IsBlank(bool $expect = true)] // returns true for null, empty string "", string containing only whitespace, empty array, empty iterable
  - #[Assert\Contains(string|array|iterable $haystack, null|"start"|"end" $at)] // Checks whether the input value, interpreted as a sequence, appears as a contiguous subsequence of the $haystack sequence. Both input and haystack must be of the same kind (string ↔ string, array|iterable ↔ array|iterable). Thows GuardException::invalidValue() in case of type mismatch.
  - Already implemented, some modifications needed (no need for BC, deprecation, etc..):
    - DateFormat
    - Email
    - EnumBackedValue
    - EnumCase
    - InArray // TODO: rename to `In`
    - InstanceOfClass // TODO: rename to InstanceOf
    - IsArray // TODO: fold into IsType('array')
    - IsFloat // TODO: fold into  IsType('float')
    - IsInteger // TODO: fold into IsType('int')
    - IsNumeric // TODO: fold into IsType('numeric') or IsType(['int', 'float', ...])
    - IsNumericString
    - Length
    - NotBlank // TODO: rename/replace by #[Assert\IsBlank(bool $expect = true)]
    - NotNull // TODO: rename/replace by #[Assert\IsBlank(bool $expect = true)]
    - Range(?float $min = null, ?float $max = null, bool $inclusive = true)
    - Regex
    - Url
    - Uuid

- **[077]** Add `#[Assert\CompareTo($operator, $value)]`, `#[Assert\CompareToExtract($operator, $path)]`
- **[028]** Add nested DTO support with `CastTo\Dto(class, groups: 'api')` (from array or object)
  - support recursive normalization and validation
- **[064]** Add framework-specific ValidationException mappers in adapters
  - Add a "ProcessingErrorMapperInterface" for adapter overrides
  - Core DTOT will throw a generic ValidationException on validation failure
  - Allow adapters (e.g., dtot-adapter-laravel, dtot-adapter-symfony) to map or wrap ValidationExceptions into framework-native exceptions
  - This enables seamless integration with Laravel and Symfony's validation error handling mechanisms
  - Keep validation logic and error reporting fully pluggable and framework-agnostic

### Preferable before v1.0 (negotiable)
- **[056]** Support multi-step casting by making withGroups(inboundCast: ...) take a sequence of group(s)
  Then apply each step in sequence. Same with outboundCast.
- **[046]** Add modifier `#[SkipIfMatch(array $values, $return=null)]`, allows short-circuitting following caster(s) by returning \$return if input is found in \$values or input in $values.
- **[071]** Add `#[CastTo\Coalesce(array, $ignore = [null])]` - takes an array and return first element not in $ignore list.
- **[062]** Add support for getCaster() debug mode
  - When enabled, caster closures push/pop debug context during execution
  - On casting failure, TransformExceptions can include full chain trace
  - Example message: "PropName: Caster1->Caster2->FailNextTo(PerItem(Caster3 -> Caster4))"
- **[048]** *Add debug mode setting. When enabled, add casting stack tracking (push/pop) to enable logging full context when failing within a chain.*
- **[050]** Add `#[LogCast($debugOnly = true)]` to also allow logging non-failing chains.

### Post v1.0
- **[080]** Port domain-specific asserts from Symfony (post v1.0)
  - Ip, StartsWith, Bic, CardScheme, Currency, Luhn, Iban, Isbn, Issn
- **[083]** Consider a modifier-based mechanism for dynamically overriding node arguments via extraction (DynamicArg or ExtractToArg).
- **[044]** Add support for DTO transforms (`\$dto->toDto($otherDtoClass)`) [See details](#PBI-044)
- **[082]** Introduce new processor node type more similar to modifiers: rather than having a single cast() or validate() method, they'd have a makeValidator()/makeCaster() method, that can return an optimized Closure, which might be different depending on $constructorArgs.
- **[034]** Add support for logging failed casts in FailTo/FailNextTo. `CastTo::$castSoftFailureLogger = function (CastingException $e, $returnedVal)`
- **[058]** Add a doc about FullDto and making one's own slimmed-down version if not all features are needed

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
- [015] Extract trait ProcessesFromAttributes
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
- [066] Add BaseDto::newFromEntity($object)
- [052] Add Casters fromJson, JsonExtract, NumericString, Base64Encode/Decode, RegexSplit
- [060] Extract inject() functionality from caster to IsInjectable trait, rename #[Injected] to #[Inject]
- [061] let DtoBase use IsInjectable, make sure $dto->inject() is called after new instanciation
- [068] Add casters to convert input to camelCase, kebab-case, PascalCase and snake_case
- [067] Fix to/fromEntity getEntityGetters() and setEntitySetters() to also check for camelCase methods for snake_cased properties
- [053] Add trait UsesLocaleResolver, CastTo\LocalizedNumericString, LocalizedCurrency, LocalizedDateTime.
- [049] Add `#[Collect(count: N)]` and #[Wrap(N)] (`#[NoOp]` = sugar for #[Wrap(0)]). Aggregate the result of the next N subchains into an array.
- [059] Add #[Wrap(N)] chain modifier that does nothing but wraps a subchain. Could be useful with #[Collect(N)]
- [051] Add modifier `#[ApplyIf(condition, count=1, negate=false)]` (and suggar `#[SkipNextIf]` )
- [065] Add FirstSuccess chain modifier: #[FirstSuccess($count)]
- [040] Add `#[MapFrom(string|array $fields)]`
- [069] Add  #[WithDefaultGroups(...)] class attribute, takes same params as UsesGroups::_withGroups() and auto-applies them after instanciation
- [020] Add `#[MapTo(...)]` Attribute [See details](Attributes.md)
- [055] Add `#[Extract(string|array $roots)]` to allow mapping from one or more internal properties or values already cast in a previous step, rather than external input.
- [045] Add support for validation [See details](#PBI-045)
- [063] Rename Caster Chains to Processing Chains and add Validation attributes as part of chains
- [072] Add support for flexible error handling and error collection, so adapters can support native framework error handling.
- [078] Introduce PresencePolicy to clear up semantics around `null` vs `missing` input values.
- [073] Refactor DTO pipeline methods (from*, with*) to get psalm happy w/o suppress.
- [074] Consider renaming or aliasing Validate namespace to Assert, and revise namespace aliasing suggestions.
- [079] Add `#[Mod\Assert($count)]`: run all sub-nodes in parallel with same input, bubble errors and return the original value.
- [081] Introduce #[ErrorTemplate] override Attribute
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
    - `CastTo::getProcessingNodeClosureMap(BaseDto $dto, array $groups = [])` should filter out processing attributes that declare `groups` not intersecting with the active group set
    - `ProcessesFromAttributes` must pass `groups` into processing closure map resolution
- **Caster syntax**: Caster attributes should allow optional `groups: string|array`
    - `#[CastTo\Slug(groups: ['public', 'seo'])]`
- **Future-compatible**
    - Architecture should anticipate applying scoping groups to validation (e.g. `#[Assert\NotBlank(groups: ['api'])]`)

### <a id="PBI-044"></a>🔄 PBI 44: Add support for DTO-to-DTO transformation

> “DTO transforms allow one DTO to be converted into another through a structured, group-aware data handoff.”
- **Example use case**: `$apiDto = $internalDto->toDto(ApiUserDto::class, groups: ['api']);`
- **Public API**
  - Add `BaseDto::toDto(string $targetDtoClass, ?array $groups = null): static`
    - Default implementation:  `return (new $targetDtoClass)->loadDto($this, $groups);`
  - Add `BaseDto::newFromDto(BaseDto $sourceDto, ?array $groups = null): static`
    - Default implementation:`return $this->loadArray($sourceDto->toOutboundArray(groups: $groups));`
- **Responsibility**
  - The **target DTO** is responsible for requesting+interpreting the source DTO’s output
  - This supports overriding `fromDto()` to customize data interpretation, grouping, or mapping logic

- **Optional override hooks**: Subclasses may override `fromDto()` to bypass outbound casting, apply custom property mapping or normalize derived data.
