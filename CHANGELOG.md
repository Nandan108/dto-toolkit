# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added
- New documentation: Mapping, Fundamentals, Comparison
- Linked additional docs from README
- Design philosophy updates (zero magic, opt-in magic)

---

## [v0.1.0] - 2024-04-11

### Added
- Initial release of DTOT core
- `BaseDto` foundation and core traits:
  - `CreatesFromArray`
  - `ExportsToEntity`
  - `NormalizesFromAttributes`
- `CastTo` with dynamic and class-based casters
- `CanCastBasicValues` trait with static helpers
- `Core\FullDto` as batteries-included base class
- Lifecycle hooks: `postLoad()` and `preOutput()`
- Initial README.md and docs: Casting, Debugging, Project philosophy
