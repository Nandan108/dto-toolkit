# 🔁 DTO Lifecycle

Understanding the lifecycle of a DTO helps clarify when casting, validation, hooks, and transformations are applied.

This section outlines the phases your DTO goes through, from input to output, separated in two distinct phases: _inbound_ then _outbound_.

---
## Inbound Phase (processing: validation + casting)

### 1. 🏗️ DTO Creation (from Input)

A new DTO instance is generally created by a static call to either a `from*` or a `with*` function. `from*` functions populate the new DTO instance's properties, while `with*` populates its context (often needed before a `from*` call).
Behind the scenes, static calls to these `from*` and `with*` calls are handled by `__callStatic()`, which first generates a new instance with `::newInstance()`, then forwards the initial call to a `_from*` or `_with*` method on the new instance. Existing methods are:
- **`_fromArray(array $input)`** takes an array of values to populate the DTO
- **`_fromEntity(object $entity)`** takes an object to extract values from to populate the DTO
- **`_fromModel(Model $model)`** to populate a DTO from an Eloquent Model *(planned for the Laravel adapter)*
- **`_fromRequest(Request)`** to populate a DTO from an HTTP request *(planned for both Laravel and Symfony adapters)*
- **`_withContext(array $values)`** takes an associative array of values and sets its elements to the DTO's context
- **`_withGroups($all, $inbound, ...)`** allows setting an "operational scope" by specifying groups for all or individual DTO lifecycle phases. This affects which properties and processing steps (nodes) are applied depending on the use of #[PropGroups(...)] and #[Groups(...)] attributes.

1. Under the hood, the `::newInstance()` static method is used to instantiate the DTO, possibly injecting necessary dependencies ([See DI doc for more details](DI.md)).
  ⚠️ DTO props are not initialized in the constructor, therefore each must have a default value (generally null).
  ⚠️ Public properties whose names start with `_` are treated as internal: they are skipped for input/output and processing.
2. Public properties are populated with raw input values (excluding `_`-prefixed props).
  ⚠️ This means that the types of public properties must allow raw input value types to be stored.
3. Each property considered “present” by its `PresencePolicy` is recorded in `$this->_filled` (default: any provided value, including `null`). Override per DTO or property with `#[Presence(...)]`.
4. Property values then flow through the inbound **processing chain** (validators + casters, optionally wrapped/controlled by modifiers) defined by attributes on the property (see section 2).
  ⚠️ This means that the types of public properties must also accomodate their post-processing type.

E.g. a birthdate property could have type `null|string|DateTimeImmutable`, to support:
   1. the initial, pre-load null value
   2. the post-load, pre-processing raw string value
   3. the post-processing date-time object's type


---

### 2. 🔄 Processing (validators + casters, modifiers control flow)

If the DTO implements `ProcessesInterface` (via the `ProcessesFromAttributes` trait), all processing nodes before `#[Outbound]` are applied in order:
- **Validators** (`#[Validate\...]`) run in-chain and fail fast on the first violation
- **Casters** (`#[CastTo\...]`) transform the value
- **Modifiers** (`#[Mod\...]`) alter the control flow of whatever follows (validators or casters). For example `#[Mod\FirstSuccess]` can wrap multiple validators to accept any one passing range check.

For properties marked as *`filled`*, this phase:
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

This is done by calling one of:
- `toOutboundArray()` - returns outbound-cast DTO content as an array
- `toEntity(object $entity = null)` (trait `ExportsToEntity`)
   - prepares outbound data
   - uses passed entity or instantiates a new one
   - loads result into the entity via public props or setters, then returns entity
- `toDto()` - for DTO-to-DTO transformations *(coming soon)*
- `toResponse()` or `toModel()` *(coming soon via adapters)*

**Steps:**
- Array produced from DTO data
- Outbound transformations applied (casting attributes that come after `#[Outbound]`)
- Entity hydration
- `PreOutput($entity)` hook called
- result returned

This phase ensures clean, typed, or enriched output — e.g., transforming strings into DateTime objects or enums.

---

### Summary Timeline

```text

Inbound :
   $dto = MyDto::fromArray() or ::fromRequest()
      ↓
      Instantiation via newInstance()
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
   $dto->toEntity() / toResponse() / ...
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
