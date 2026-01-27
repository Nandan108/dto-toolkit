# 🔁 DTO Lifecycle

Understanding the lifecycle of a DTO helps clarify when casting, validation, hooks, and transformations are applied.

This section outlines the phases your DTO goes through, from input to output, separated in two distinct phases: _inbound_ then _outbound_.

> **Note on phases**
>
> While this document describes an “inbound” and an “outbound” phase for clarity, most processing nodes (casters, validators, modifiers) are **phase-agnostic**.
> Whether a given attribute runs during inbound or outbound processing depends on its position relative to `#[Outbound]`, not on the attribute itself.

---

## Inbound Phase (processing: validation + casting)

### 1. 🏗️ DTO Creation (from Input)

A new DTO instance is generally created by a static call to either a `newFrom*` or a `newWith*` function. `newFrom*` functions populate the new DTO instance's properties, while `newWith*` populates its context (often needed before a `newFrom*` call).
Behind the scenes, static calls to these `newFrom*` and `newWith*` calls are handled by `__callStatic()`, which first generates a new instance with `::new()`, then forwards the initial call to a `load*` or `with*` method on the new instance. Existing methods are:

- **`loadArray(array $input)`** takes an array of values to populate the DTO
- **`loadEntity(object $entity)`** takes an object to extract values from to populate the DTO
- **`loadModel(Model $model)`** to populate a DTO from an Eloquent Model _(planned for the Laravel adapter)_
- **`loadRequest(Request)`** to populate a DTO from an HTTP request _(planned for both Laravel and Symfony adapters)_
- **`withContext(array $values)`** takes an associative array of values and sets its elements to the DTO's context
- **`withGroups($all, $inbound, ...)`** allows setting an "operational scope" by specifying groups for all or individual DTO lifecycle phases. This affects which properties and processing steps (nodes) are applied depending on the use of #[PropGroups(...)] and #[Groups(...)] attributes.

1. Under the hood, the `::new()` static method is used to instantiate the DTO, possibly injecting necessary dependencies ([See DI doc for more details](DI.md)).
   ⚠️ DTO props are not initialized in the constructor, therefore each must have a default value (generally null).
   ⚠️ Public properties whose names start with `_` are treated as internal: they are skipped for input/output and processing.
2. Public properties are populated with raw input values (excluding `_`-prefixed props).
   ⚠️ This means that the types of public properties must allow raw input value types to be stored.
3. Each property considered “present” by its `PresencePolicy` is recorded in `$this->_filled` (default: any provided value, including `null`). Override per DTO or property with `#[Presence(...)]`.
4. Property values then flow through the inbound **processing chain** (validators + casters, optionally wrapped/controlled by modifiers) defined by attributes on the property (see section 2).
   ⚠️ This means that the types of public properties must also accomodate their post-processing type.

E.g. a birthdate property could have type `null|string|DateTimeImmutable`, to support:

1.  the initial, pre-load null value
2.  the post-load, pre-processing raw string value
3.  the post-processing date-time object's type

---

### 2. 🔄 Processing (validators + casters, modifiers control flow)

If the DTO implements `ProcessesInterface` (via the `ProcessesFromAttributes` trait), all processing nodes before `#[Outbound]` are applied in order:

- **Validators** (`#[Assert\...]`) run in-chain and fail fast on the first violation
- **Casters** (`#[CastTo\...]`) transform the value
- **Modifiers** (`#[Mod\...]`) alter the control flow of whatever follows (validators or casters). For example `#[Mod\Any]` can wrap multiple validators to accept any one passing range check.

For properties marked as _`filled`_, this phase:

- Applies method-based or class-based validators, casters, and modifiers
- Transforms raw values into the appropriate internal types
  ⚠️ Public property types must allow post-processing value types to be stored.

Only properties marked as filled will be processed unless future features (e.g., `#[AlwaysCast]`) are added.

---

### 3. 🧩 Post-Processing and Pre-Export hooks (`postLoad() / preOutput()`)

If the DTO class defines a `postLoad()` method, it will be invoked **after inbound processing**.

Use this to:

- Derive or compute additional values
- Perform cross-field logic
- Modify the DTO in-place before application logic

Similarely, if the DTO class defines a `preOutput($outputData)` method, it will be invoked **after outbound processing**.
Use this hook for final preparation or modification of `$outputData`, right before it is returned.

> Note that hooks **should not** introduce side effects outside the DTO

---

## Outbound Phase

This second phase concerns exporting the DTO data to another form for use by the application.

Recursive export follows the same directional semantics as the initiating call: nested DTOs are processed using the same target (array or entity) and the same execution context. If one of the inner DTOs is not exportable in the same direction, a configuration error is raised. For partial exports, use explicit casting on individual properties.

This is done by calling one of:

- `toOutboundArray()` - returns outbound-cast DTO content as an array (processing + export primitive, non-recursive)
- `exportToEntity(object|class-string|null $entity = null, array $supplementalProps = [], bool $recursive = false)` *(trait `ExportsOutbound`, or if you want a strongly typed return: `ExportsOutboundTyped`)*
  - prepares outbound data
  - uses passed entity or instantiates a new one
  - loads result into the entity via public props or setters, then returns entity
- `exportToArray(array $supplementalProps = [], bool $recursive = false)` *(trait `ExportsOutbound`)*
  - returns outbound-cast DTO content as an array, with optional recursive DTO export
- `toResponse()` or `toModel()` _(coming soon via adapters)_

### **Traits overview**

For a full overview of the default trait bundle in `FullDto` (including `ExportsOutbound` vs `ExportsOutboundTyped`), see [Traits.md](Traits.md).

### **Nested processing context**

During nested processing (inbound or outbound), the context defined on the **outermost DTO**—via `->withContext()`, `->withGroups()`, `::newWithContext()`, etc.—is stored in the `ProcessingContext` and is the context **visible to all inner DTOs** while processing is active.

This applies both to inbound processing that triggers nested DTO work (for example, `DtoClass::newWithContext($ctx)->loadArray($input)` when some properties of `DtoClass` are themselves DTOs) and to recursive exports (such as `exportToEntity()` or `exportToArray()` with `$recursive = true`).

Inner DTO instances still retain their own internal `_context` values, but those values are **temporarily ignored** while a processing frame is active; the `ProcessingContext` always takes precedence.
This makes context an **execution-scoped concern**, not an object-scoped one: nested DTOs participate in the same processing context as the operation that triggered them.

**Steps:**

- Array produced from DTO data
- Outbound transformations applied (casting attributes that come after `#[Outbound]`)
- Entity hydration
- `PreOutput($entity)` hook called
- result returned

This phase ensures clean, typed, or enriched output — e.g., transforming strings into DateTime objects or enums.

---

### Summary Timeline
> This timeline illustrates a common imperative flow. Declarative processing (`CastTo\Dto`, `CastTo\Entity`, `CastTo\AsArray`) and recursive traversal follow the same execution model but may occur within property-level processing chains.

```text

Inbound :
   $dto = MyDto::newFromArray() or ::newFromRequest()
      ↓
      Instantiation via new()
      ↓
      Raw values assigned
      ↓
      Inbound Processing (validation and normalization)
      ↓
      postLoad() hook
      ↓
      returns hydrated $dto instance, ready for business logic.

Outbound :
   $dto->toOutboundArray()
      ↓
      Outbound processing (validators/casters after #[Outbound])
      ↓
      preOutput() hook
      ↓
      returns array
or:
   $dto->exportToEntity() / toResponse() / ...
      ↓
      toOutboundArray()
            ↓
            Outbound processing (validators/casters after #[Outbound])
      ↓
      hydrate output object
      ↓
      preOutput() hook
      ↓
      returns entity/response/...
```
