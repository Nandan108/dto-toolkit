# Toolkit Comparison

There are several libraries in the PHP ecosystem that offer data handling, mapping, validation, or DTO-like features. This document outlines how `nandan108/dto-toolkit` compares to some of the most common options.

---

## 🔍 Feature Comparison

| Feature                      | DTO Toolkit | Spatie DTO | Symfony Tools      | Valinor | Laravel            |
| ---------------------------- | ----------- | ---------- | ------------------ | ------- | ------------------ |
| Framework-agnostic           | ✅          | ✅         | ❌ (Symfony-bound) | ✅      | ❌ (Laravel-bound) |
| Attribute-driven casting     | ✅          | ❌         | ⚠️ (yaml/xml/attr) | ✅      | ⚠️ (manual logic)  |
| Declarative syntax           | ✅          | ⚠️         | ❌                 | ⚠️      | ⚠️                 |
| Pluggable adapters           | ✅          | ❌         | ❌                 | ❌      | ❌                 |
| Input/output transformation  | ✅          | ❌         | ⚠️                 | ✅      | ⚠️                 |
| Validation system separation | ✅          | ❌         | ❌                 | ✅      | ❌                 |
| Pre/post lifecycle hooks     | ✅          | ❌         | ❌                 | ❌      | ⚠️                 |
| “Zero magic” design          | ✅          | ✅         | ❌                 | ❌      | ❌                 |
| Testing and DX focus         | ✅          | ✅         | ⚠️                 | ⚠️      | ⚠️                 |

Legend:

- ✅ Native or first-class support
- ⚠️ Partial or possible with effort
- ❌ Not supported or not idiomatic

---

## 🧠 Philosophy Alignment

| Principle              | DTO Toolkit                         |
| ---------------------- | ----------------------------------- |
| Separation of concerns | ✅ Clean trait-based layering       |
| Composability          | ✅ Core traits and hooks            |
| Minimalism             | ✅ Core stays lean                  |
| Expressiveness         | ✅ Attributes and static helpers    |
| Adaptability           | ✅ Adapter system, opt-in behaviors |

---

## 📌 Summary

`nandan108/dto-toolkit` offers:

- A **modern, attribute-based** way to handle input/output data
- **No framework lock-in**
- An **adapter-friendly core** for use in Laravel, Symfony, or standalone apps
- A philosophy centered on **clarity, composability, and zero surprises**

It's not trying to replace full-stack frameworks — just to make DTOs a joy to work with.
