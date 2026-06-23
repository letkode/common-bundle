# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [1.0.3] - 2026-06-23

### Changed
- `UniqueField`: renamed `ignoreProperty` → `skipBySelfProperty`, `ignoreRouteParam` → `skipRouteParamValue`, and `ignoreEntityField` → `skipByRouteFieldProperty` for clarity and naming consistency.

### Fixed
- `UniqueFieldValidator::skipRouteParamValue` — replaced `getIdentifierValues()` (which returns the integer PK) with `ReflectionProperty` reading the entity field named by `skipByRouteFieldProperty` (default `'uuid'`). This fixes the comparison always failing on entities that use an integer `$id` primary key and a separate `$uuid` field.

---

## [1.0.2] - 2026-06-23

### Added
- `UniqueField::$ignoreRouteParam` — skip uniqueness check when the existing record's ID matches a route parameter (e.g. `{uuid}` in PUT endpoints)
- `UniqueFieldValidator` now injects `RequestStack` to resolve the current route parameter value

---

## [1.0.1] - 2026-06-19

### Fixed
- Widen `doctrine/persistence` constraint to `^3.0 || ^4.0`

---

## [1.0.0] - 2026-06-19

### Added
- Initial release as `letkode/common-bundle`
- Symfony bundle integration via `LetkodeCommonBundle` extending `AbstractBundle`
- Auto-discovery support via `extra.symfony.bundles` in Composer
- **Exceptions**: `HttpStatusExceptionInterface`, `BadRequestException`, `EntityNotFoundException`, `TooManyRequestsException`, `UnauthorizedException`, `ValueObjectException`
- **Attributes**: `UniqueField` constraint + `UniqueFieldValidator`, `MapUuid` mapping attribute
- **Value Resolver**: `UuidValueResolver` — resolves `Uuid` route parameters automatically
- **Utils**: `BuilderUrlClient` (requires `letkode/helpers-bundle`), `JsonReader`

### Requirements
- PHP `^8.4`
- Symfony `^7.0 || ^8.0`
- `doctrine/persistence` `^3.0`
- `letkode/helpers-bundle` `^1.0`

[Unreleased]: https://github.com/letkode/common-bundle/compare/1.0.3...HEAD
[1.0.3]: https://github.com/letkode/common-bundle/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/letkode/common-bundle/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/letkode/common-bundle/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/letkode/common-bundle/releases/tag/1.0.0
