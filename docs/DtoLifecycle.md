## 🔁 DTO Lifecycle

Understanding the lifecycle of a DTO helps clarify when casting, validation, hooks, and transformations are applied.

This section outlines the phases your DTO goes through, from input to output, separated in two distinct phases: _inbound_ then _outbound_.

---
### Inbound Phase

#### 1. 🏗️ DTO Creation (from Input)

A new DTO instance is generally created directly from an input source. E.g. using `fromArray()`, `fromEntity()` or (via adapter), `fromRequest()`.

1. Under the hood, the ::newInstance() static method is used to instantiate the DTO, possibly injecting necessary dependencies ([See DI doc for more details](DI.md)).
  ⚠️ DTO props are not initialized in the constructor, therefore each must have a default value (generally null).
2. Public properties are populated with raw input values.
  ⚠️ This means that the types of public properties must allow raw input value types to be stored.
  E.g. a birthdate property could have type null|string|DateTimeImmutable, which supports 1. the initial, pre-load null value, 2. the post-load, pre-normalization raw string value, 3. the post-normalization date-time object's type.
3. Each property filled is recorded in `$this->_filled`.

This happens *before* any casting or transformation is applied.

---

#### 2. ✅ Validation (on Raw Input)

Validation — when enabled via an adapter — is performed **before normalization**, directly on the raw values assigned from input.

This ensures:

- Accurate feedback tied to original input
- No risk of hiding invalid input due to coercion or default-casting
- Predictable error reporting

> ⚠️ Validating normalized values can lead to misleading errors, especially for formats like dates, booleans, or numbers.

---

#### 3. 🔄 Normalization

If the DTO implements `NormalizesInboundInterface`, all `#[CastTo(...)]` attributes before #[Outbound] are applied.

For properties marked as *`filled`*, this phase:
- Applies method-based or class-based casters (like `#[CastTo\Floating]`)
- Transforms raw values of  into the appropriate internal types
  ⚠️ This means that the types of public properties must also allow post-transformation value types to be stored.

Only properties marked as filled will be normalized unless future features (e.g., `#[AlwaysCast]`) are added.

---

#### 4. 🧩 Post-Processing (`postLoad()` Hook)

If the DTO class defines a `postLoad()` method, it will be invoked **after normalization**.

Use this to:
- Derive or compute additional values
- Perform cross-field logic
- Modify the DTO in-place before application logic

---

### Outbound Phase

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
- Outbound transformations applied (casting attributes that come after #[Outbound])
- Entity hydration
- `PreOutput($entity)` hook called
- result returned

This phase ensures clean, typed, or enriched output — e.g., transforming strings into DateTime objects or enums.

---

#### Summary Timeline

```text

Inbound :
   $dto = MyDto::fromArray() or ::fromRequest()
      ↓
      Instantiation via newInstance()
      ↓
      Raw values assigned
      ↓
      Validation (on raw input, using external validator)
      ↓
      Normalization (inbound casting)
      ↓
      postLoad() hook
      ↓
      returns hydrated $dto instance, ready for business logic.

Outbound :
   $dto->toOutboundArray()
      ↓
      Outbound casting
      ↓
      preOutput() hook
      ↓
      returns entity
or:
   $dto->toEntity() / toResponse() / ...
      ↓
      toOutboundArray()
            ↓
            Outbound casting
      ↓
      hydrate output object
      ↓
      preOutput() hook
      ↓
      returns entity/response/...
```
