# DTO Fundamentals

## 🧱 What is a DTO?

A **Data Transfer Object (DTO)** is a plain object used to move structured data between layers or systems. It carries data, not behavior. DTOs are commonly used to:

- Receive data from a client (e.g. an HTTP request)
- Prepare data for output (e.g. an API response)
- Act as a staging area before persisting to or loading from a database
- Transform data formats between boundaries

DTOs promote clarity, consistency, and type safety, especially in complex applications.

---

## 🎯 Why Use DTOs in Modern PHP?

PHP 8+ has brought powerful new tools to the language:
- Attributes
- Typed properties
- Constructor promotion
- Enums
- Reflection improvements

These features make DTOs easier and more expressive than ever. They are now a first-class citizen in modern application architectures.

---

## ⚙️ When to Reach for a DTO

DTOs shine when:
- You want to **decouple request data from domain models**
- You need to **validate and transform input** before using it
- You want **type-safe, self-documenting interfaces** between layers
- You want a clear separation between your internal data and external representation

---

## ✅ Benefits of DTOs

| Benefit                  | Description                                         |
|--------------------------|-----------------------------------------------------|
| **Type safety**          | Declares and enforces data shape                    |
| **Explicit mapping**     | Prevents accidental data leakage or inconsistencies |
| **Testable and predictable** | Easy to unit test, serialize, and reason about  |
| **Framework-agnostic***  | Not relying on a framework, can be used with any or none  |
| **Input/output symmetry**| DTOs can represent both incoming and outgoing data  |

\*Subject to implementation — see [Comparison of DTO libraries](Comparison.md)

---

## 🔄 DTOs vs. Models vs. Arrays

| Use Case              | Recommended Structure |
|-----------------------|------------------------|
| Raw request data      | ✅ DTO (`fromArray`, `fromRequest`) |
| Domain persistence    | ✅ Entity / Model       |
| Simple config         | ⚠️ Array                |
| Reusable logic object | ❌ Avoid using DTOs     |

DTOs are not a dumping ground for behavior or unrelated data. They're lean by design.

---

## 🧪 DTOs in Practice

In this toolkit:

- Input DTOs are created with `fromArray()`, `fromRequest()`, etc.
- Output DTOs can produce `toArray()`, `toEntity()`, `toModel()`...
- Traits and attributes provide validation, casting, and mapping
- Adapters let you plug into Symfony, Laravel, or your own architecture

---

## ✨ Summary

DTOs are a powerful way to:
- **Separate structure from behavior**
- **Validate and transform data**
- **Communicate intent through type-safe, minimal objects**

They belong in every modern PHP developer’s toolbox — especially when combined with expressive, attribute-driven toolkits like this one.
