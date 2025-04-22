## 🔁 DTO Lifecycle

Understanding the lifecycle of a DTO helps clarify when casting, validation, hooks, and transformations are applied.

This section outlines the phases your DTO goes through, from input to output, separated in two distinct phases: _inbound_ then _outbound_.

---
### Inbound Phase

#### 1. 🏗️ DTO Creation (from Input)

The DTO is created using `fromArray()` or (via adapter) `fromRequest()`:

- Public properties are populated with raw input values.
- Each property filled is recorded in `$this->_filled`.

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

This phase:
- Transforms raw values into the appropriate internal types
- Uses method-based or class-based casters (like `#[CastTo\Floating]`)

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
- `toEntity()` - internally calls `toOutboundArray()` then hydrates the result into an object via public props or setters.
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
      Raw values assigned
      ↓
      Validation (on raw input)
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
