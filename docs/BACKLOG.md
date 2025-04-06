# Symfony DTO Toolkit Backlog

## To Do

18. Implement unit tests for existing features (and add testing to DoD)
30. Add support for CasterInterface (refactor CastTo to take care of returning a casting Closure)
19. Add support for chaining multiple #[CastTo] attributes (Attribute::IS_REPEATABLE)
20. Add #[MapTo('actualPropName')] for DTO → Entity name mismatches
21. Add #[IgnoreMapping] attributes to ignore certain props
22. Add #[CustomSetter('setSomethingSpecial')] to override default name
23. Add toOutputArray() for array output with application of outbound casting
24. Refactor toEntity() to base it on toOutputArray() + getEntitySetterMap() + using setters
25. Extract trait + interface for ValidatesInput
26. Extract trait + interface for CreatesFromArray
27. Extract trait CreatesFromRequest (uses CreatesFromArray)
28. Add nested DTO support with recursive normalization + validation
29. CastTo for array items (CastArrayItemsTo Attribute or $applyToArrayItems argument?)

## Completed

1. Scaffold package with composer.json and PSR-4 autoloading
2. Introduce BaseInputDto with validated() function
3. Introduce fromRequest() and mechanism to get input from Request
4. Improve fromRequest() to allow flexible declaration of input sources
5. Add toEntity() to instanciate an entity from DTO data
6. Add normalize() method for casting inbound data to DTO's types
7. Add normalizeToDto() and normalizeToEntity()
8. Refactor toEntity() to use normalizeToEntity() and getEntitySetterMap()
9. Refactor toArray() to accept prop list
10. Make getEntitySetterMap() validate context props
11. Introduce CastTo attribute and casting helpers
12. Implement default castToIntOrNull, castToStringOrNull, castToDateTimeOrNull
13. Implement entity setter map with reflection and fallback
14. Rename BaseInputDto to BaseDto
15. Extract trait NormalizesFromAttributes
16. Add support for #[CastTo(..., outbound: true)] on output DTOs
17. Allow parameterized casts (e.g. separator for CSV)

