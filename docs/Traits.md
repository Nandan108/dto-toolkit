# 🧩 Traits

This document describes the traits that power the default DTO base class and how to opt into typed outbound exports.
Traits are the primary composition mechanism in DTOT: each one adds a focused capability to a DTO.

---

## FullDto at a glance

`FullDto` is the default base class most DTOs will extend. It mixes in a set of traits so you get the full feature set without manual wiring.
It represents the “everything included” configuration and is suitable for the vast majority of use cases.

Traits used by `FullDto`:

- `UsesGroups` - group scoping + property filtering; includes `HasContext`
- `CreatesFromArrayOrEntity` - `newFrom*` / `load*` helpers for arrays and entities
- `ProcessesFromAttributes` - inbound/outbound processing via attributes
- `ExportsOutbound` - `exportToEntity()` + `exportToArray()` helpers
- `IsInjectable` - `#[Inject]` support for DI

If you want to pick and choose, for instance to make a less full-featured base class, extend `BaseDto` and add only the traits you need.

---

## Traits in FullDto

### HasContext

Provides a lightweight context store that lives on the DTO instance and is read by processing/scoping logic.

Key capabilities:

- Implements: `HasContextInterface`
- Provides: `withContext()` plus `contextGet()` / `contextSet()` helpers
- Exposes: `getContext()` returns the current execution context data when processing is active

### UsesGroups

Adds group scoping and scoped property access for inbound/outbound phases.

Key capabilities:

- Implements: `HasGroupsInterface`, `ScopedPropertyAccessInterface`
- `withGroups(...)` and `getActiveGroups()` for per-phase group scoping
- `getPropertiesInScope()` for property filtering by groups
- `withContext()` for execution-scoped values

### CreatesFromArrayOrEntity

Provides `newFrom*`/`load*` helpers and array/entity extraction.

Key capabilities:

- Implements: `CreatesFromArrayOrEntityInterface`
- `loadArray()` / `loadArrayLoose()` for array input
- `loadEntity()` for extracting values from objects
- `MapFrom` support + presence policy handling during fill

### ProcessesFromAttributes

Runs validators and casters defined by attributes on your DTO.

Key capabilities:

- Implements: `ProcessesInterface`
- `processInbound()` for inbound normalization/validation
- `processOutbound()` for outbound casting after `#[Outbound]`

### ExportsOutbound

Outbound helpers for entity and array exports.

Key capabilities:

- Implements: none
- `exportToEntity()` returns an `object`
- `exportToArray()` returns an array (optionally recursive)

### IsInjectable

Populates `#[Inject]`-decorated properties using the container bridge, post-instantiation.

Some casting and validation node producers may also require service injection. This need can be met by using this trait and implementing `Injectable`, so that `->inject()` is first called post-instantiation, followed by `->boot()` if the producer class implements `Bootable`.

Key capabilities:

- Implements: `Injectable`
- `inject()` resolves and sets typed properties via `ContainerBridge`

See [DI.md](DI.md).

---

## Untyped vs Typed Outbound Exports

`ExportsOutbound` is the default outbound helper and keeps `exportToEntity()` untyped (returns `object`). It is already included by `FullDto`.

If you want a strongly typed return, use `ExportsOutboundTyped` in your DTO and provide the template parameter at the usage site. This overrides only `exportToEntity()` while keeping `exportToArray()` from `FullDto`.
Use this when your DTO has a single, well-defined outbound entity type and you want static analysis support.

Implements: none

```php
final class UserDto extends FullDto // or BaseDto
{
    /** @use ExportsOutboundTyped<UserEntity> */
    use ExportsOutboundTyped;
}
```

For outbound flow details, see `docs/DtoLifecycle.md`.
