# DTO Toolkit Backlog

## Product Backlog Items

### Upcoming
- **[064]** Add framework-specific ValidationException mappers in adapters
  - Add a "ProcessingErrorMapperInterface" for adapter overrides
  - Core DTOT will throw a generic ValidationException on validation failure
  - Allow adapters (e.g., dtot-adapter-laravel, dtot-adapter-symfony) to map or wrap ValidationExceptions into framework-native exceptions
  - This enables seamless integration with Laravel and Symfony's validation error handling mechanisms
  - Keep validation logic and error reporting fully pluggable and framework-agnostic
- **[056]** Support multi-step casting by making withGroups(inboundCast: ...) take a sequence of group(s)
  Then apply each step in sequence. Same with outboundCast.
- **[062]** Add support for getCaster() debug mode
  - When enabled, caster closures push/pop debug context during execution
  - On casting failure, TransformExceptions can include full chain trace
  - Example message: "PropName: Caster1->Caster2->FailNextTo(PerItem(Caster3 -> Caster4))"
- **[048]** Add debug mode setting. When enabled, add casting stack tracking (push/pop) to enable logging full context when failing within a chain.
- **[050]** Add `#[LogCast($debugOnly = true)]` to also allow logging non-failing chains.

### For later
- **[083]** Consider a modifier-based mechanism for dynamically overriding node arguments via extraction (DynamicArg or ExtractToArg).
- **[044]** Add support for DTO transforms (`\$dto->toDto($otherDtoClass)`) [See details](#PBI-044)
- **[082]** Introduce new processor node type more similar to modifiers: rather than having a single cast() or validate() method, they'd have a makeValidator()/makeCaster() method, that can return an optimized Closure, which might be different depending on $constructorArgs.
- **[034]** Add support for logging failed casts in FailTo/FailNextTo. `CastTo::$castSoftFailureLogger = function (CastingException $e, $returnedVal)`
- **[058]** Add a doc about FullDto and making one's own slimmed-down version if not all features are needed
- **[086]** Bump min PHP version to 8.3 (and get typed constants!)
  - Move some static props to typed constants: `(Assert|CastTo)::$methodPrefix`, `GuardException::$template_prefix`,`GuardException::$error_code`, `ProcessingException::$defaultErrorCode.`

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
- [075] Port a majority of basic Symfony validation constraints (`#[Assert\...]`) to DTOT Core
- [077] Add `#[Assert\CompareTo($op, $scalar)]`, `#[Assert\CompareToExtract($op, $rightPath, $leftPath = null)]`
- [084] Add `#[CastTo\Age("seconds"|"days"|"years" $in = years, IsoDateTimeString $relativeTo = null): float]`
- [028] Add nested DTO support, `CastTo\Dto($dtoClassName)`, `CastTo\Entity($cassName)`, support recursive normalization and validation
- [087] Add `#[DefaultOutboundEntity(class, constructMode, groups)]`
- [084] `#[CastTo\Pad($length, $char = ' ', 'right'|'left' $side = 'left')]` (match str_pad() where possible)
- [085] `#[Assert\Json(array<'object'|'array'|'number'|'string'|'bool'|'null'> $allowedTypes = [])]` Validates that $value is a json object. If `$allowedTypes` provided, further checks that $value's type is one of those types. Checks can be done via string checks (e.g. array/object/string checks: $value[0] === '['/'{'/'"')
- [071] Add `#[CastTo\Coalesce($ignore = [null])]` - takes an array $value and return first element not in $ignore list. throws if $value not an array or iterator.
- [080] Port domain-specific asserts from Symfony (post v1.0): Ip, Bic, CardScheme, Currency, Luhn, Iban, Isbn, Issn
- [046] Add modifier `#[Mod\SkipIfMatch(array $matchValues, $count, $return, $strict, $negate)]`, allows short-circuitting following caster(s) by returning `$return` if input matches an element of `$matchValues`.

---

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
