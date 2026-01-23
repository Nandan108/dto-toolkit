# Toolkit Comparison

The PHP ecosystem includes several libraries that address data mapping, validation, or DTO-like concerns.
This document explains how **DTO Toolkit (DTOT)** compares — not just in features, but in **model, scope, and intent**.

DTOT is best understood not as a “better DTO mapper”, but as a **unified data-processing engine** centered around DTO lifecycles.

---

## 🧭 Where DTO Toolkit Fits

DTO Toolkit is designed for applications that need to:

- Normalize, validate, and transform data **as one coherent lifecycle**
- Handle **nested structures** symmetrically on input _and_ output
- Keep data processing **explicit, declarative, and composable**
- Remain **framework-agnostic**, while integrating cleanly via adapters

It deliberately avoids spreading concerns across multiple subsystems (forms, serializers, validators, mappers).
Instead, it models data transformation as a **single, explicit pipeline**.

---

## 🔍 Architectural Comparison

### Concepts that DTOT models as first-class

| Concept                             | DTOT            | Typical approach elsewhere |
| ----------------------------------- | --------------- | -------------------------- |
| Unified inbound/outbound lifecycle  | Explicit        | Split across tools         |
| Processing = cast + validate + flow | Single pipeline | Split across subsystems    |
| Recursive DTO processing            | Native          | Manual recursion           |
| Execution context propagation       | First-class     | Ad-hoc / implicit          |
| Control-flow semantics              | Declarative     | Hardcoded                  |

**Key takeaway:**
Other tools can _approximate_ parts of this — DTOT is the only one that **models it as a single abstraction**.

---

## 🧠 Comparison by Philosophy

### DTO Toolkit vs Spatie DTO

- Spatie DTO focuses on **typed containers**
- DTOT focuses on **data flow and transformation**
- No lifecycle, no outbound symmetry, no control flow in Spatie DTO

DTOT is not a replacement — it addresses a different problem.

---

### DTO Toolkit vs Symfony Validator / Serializer / Forms

Symfony is extremely powerful — but:

- Validation, serialization, forms, and mapping live in **separate systems**
- Cross-cutting behavior requires glue code and conventions
- Control flow (fail fast vs collect) is largely implicit

DTOT trades ecosystem breadth for **cohesion**:

- One pipeline
- One mental model
- Explicit error and flow semantics

---

### DTO Toolkit vs Valinor

Valinor is built around **immutable value object construction** as its core abstraction.

DTOT differs in that it:

- Supports **mutable, multi-phase transformation**
- Treats DTOs as **processing surfaces**, not just values
- Handles validation, transformation, and export together
- Supports immutable outputs, but treats immutability as an export concern rather than a processing invariant.

Valinor is ideal for _pure value modeling_; DTOT is built for _application data pipelines_.

---

## 🧩 Validation & Control Flow

DTOT treats validation as a **first-class processing node**, not a separate concern.

This enables:

- Phase-aware validation (inbound vs outbound)
- Explicit control over failure semantics (`FailFast`, `CollectFailToNull`, etc.)
- Modifier-based flow (`Any`, `Assert`, `FailTo`, `Wrap`, …)

Most libraries hardcode these decisions.
DTOT makes them **declarative and local**.

---

## 🧱 Minimalism (Clarified)

DTOT is **not minimal by feature count**.

It _is_ minimal in:

- Number of core abstractions
- Conceptual surface area
- Implicit behavior

Every feature is integrated into the same model:

> **DTO intantiation → input loading → processing pipeline → export**

There is no hidden magic — only composition.

---

## 🚫 When DTO Toolkit May _Not_ Be the Right Fit

DTOT may not be ideal if:

- You want **pure immutable value objects** with no transformation steps
- You only need simple array → object hydration
- You prefer framework-specific abstractions (Forms, Eloquent casts, etc.)
- You prefer configuration-driven DSLs over code-level attributes

DTOT intentionally favors **explicit, code-centric declaration**.

---

## 📌 Summary

DTO Toolkit offers:

- A **unified lifecycle** for data normalization, validation, and export
- Explicit, declarative **control over processing flow**
- First-class support for **nested DTOs and recursive transformations**
- A **framework-agnostic core** with adapter-friendly integration points
- A design philosophy focused on **clarity, composability, and predictability**

It’s not trying to replace frameworks.
It’s trying to give **data transformation a clear, explicit, and composable core**.
