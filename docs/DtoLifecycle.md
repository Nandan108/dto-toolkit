## 🔁 DTO Lifecycle

Understanding the lifecycle of a DTO helps clarify when casting, validation, hooks, and transformations are applied.

This section outlines the phases your DTO goes through, from input to output.

---

### 1. 🏗️ DTO Creation (from Input)

The DTO is created using `fromArray()` or (via adapter) `fromRequest()`:

- Public properties are populated with raw input values.
- Each property filled is recorded in `$this->_filled`.

This happens *before* any casting or transformation is applied.

---

### 2. ✅ Validation (on Raw Input)

Validation — when enabled via an adapter — is performed **before normalization**, directly on the raw values assigned from input.

This ensures:

- Accurate feedback tied to original input
- No risk of hiding invalid input due to coercion or default-casting
- Predictable error reporting

> ⚠️ Validating normalized values can lead to misleading errors, especially for formats like dates, booleans, or numbers.

---

### 3. 🔄 Inbound Normalization

If the DTO implements `NormalizesInboundInterface`, all `#[CastTo(...)]` attributes without `outbound: true` are applied.

This phase:
- Transforms raw values into the appropriate internal types
- Uses method-based or class-based casters (like `#[CastTo\FloatType]`)

Only properties marked as filled will be normalized unless future features (e.g., `#[AlwaysCast]`) are added.

---

### 4. 🧩 Post-Processing (`postLoad()` Hook)

If the DTO class defines a `postLoad()` method, it will be invoked **after normalization**.

Use this to:
- Derive or compute additional values
- Perform cross-field logic
- Modify the DTO in-place before application logic

---

### 5. 📤 Outbound Transformation

When calling:
- `toEntity()`
- `toOutboundArray()`
- Or a custom `toResponse()` (via adapter)

The following occurs:
- The DTO is turned into an array or object
- Outbound `#[CastTo(..., outbound: true)]` attributes are applied
- If `preOutput($entity)` exists, it is called before returning the result

This phase ensures clean, typed, or enriched output — e.g., transforming strings into DateTime objects or enums.

---

### Summary Timeline

```text
fromArray() or fromRequest()
   ↓
Raw values assigned
   ↓
Validation (on raw input)
   ↓
Normalization (inbound casting)
   ↓
postLoad() hook
   ↓
(toEntity() / toArray())
   ↓
Outbound casting
   ↓
preOutput() hook
```
