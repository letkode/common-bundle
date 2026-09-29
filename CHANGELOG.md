# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [2.1.0] - 2026-09-29

### Changed
- `Exception\Validation\ValueObjectException` is no longer `final`, so it can be extended. Nothing that worked before changes.

---

## [2.0.0] - 2026-09-29

### Changed
- **Breaking:** `ValueObjectException` moved from `Letkode\CommonBundle\Exception` to `Letkode\CommonBundle\Exception\Validation`.

### Removed
- **Breaking:** the HTTP status exceptions and their interface moved to the new package [`letkode/http-exception-bundle`](https://github.com/letkode/http-exception-bundle) (namespace `Letkode\HttpExceptionBundle`). They are not re-exported from `common-bundle`.

  | Before (`letkode/common-bundle` 1.x) | Now (`letkode/http-exception-bundle`) |
  |---|---|
  | `Letkode\CommonBundle\Exception\BadRequestException` | `Letkode\HttpExceptionBundle\Exception\BadRequestException` |
  | `Letkode\CommonBundle\Exception\UnauthorizedException` | `Letkode\HttpExceptionBundle\Exception\UnauthorizedException` |
  | `Letkode\CommonBundle\Exception\EntityNotFoundException` | `Letkode\HttpExceptionBundle\Exception\EntityNotFoundException` |
  | `Letkode\CommonBundle\Exception\TooManyRequestsException` | `Letkode\HttpExceptionBundle\Exception\TooManyRequestsException` |
  | `Letkode\CommonBundle\Exception\HttpStatusExceptionInterface` | `Letkode\HttpExceptionBundle\Contract\HttpStatusExceptionInterface` |

  The new package also provides the rest of the common HTTP status exceptions, `TranslationOption` and a JSON `ExceptionListener`; see its README.

### Migration
```bash
composer require letkode/http-exception-bundle
```
```php
// Before
use Letkode\CommonBundle\Exception\BadRequestException;
use Letkode\CommonBundle\Exception\HttpStatusExceptionInterface;
use Letkode\CommonBundle\Exception\ValueObjectException;

// After
use Letkode\HttpExceptionBundle\Exception\BadRequestException;
use Letkode\HttpExceptionBundle\Contract\HttpStatusExceptionInterface;
use Letkode\CommonBundle\Exception\Validation\ValueObjectException;
```
The 2nd constructor argument of the moved exceptions is now the string `errorCode` (not an int `$code`); `getCode()` is always 0. Pass the previous exception as the 3rd argument (`previous:`).

---

## [1.4.2] - 2026-08-10

### Fixed
- `phpstan.neon` added (was required as a dev dependency but never configured); package is now phpstan level 9 clean
- `UniqueField::$entityClass` and `UniqueFieldValidator` scope-value handling now use proper `class-string` typing and scalar narrowing instead of unchecked casts on `mixed`
- `UniqueFieldValidator` now throws a clear `LogicException` if a resolved `scopeResolverClass` service doesn't actually implement `UniqueFieldScopeResolverInterface`, instead of a fatal error on the next method call
- `JsonReader::read()` now throws if the decoded JSON root isn't an object/array, instead of silently returning a scalar and violating its own `array<mixed>|null` return type
- `BuilderUrlClient::extractPlaceholderKeys()` dead `?? []` fallback removed (the key always exists after `preg_match_all()`)
- `UuidValueResolver::resolve()` return type documented as `iterable<Uuid>`
- `JsonReaderTest` temp-directory cleanup fixed — it left a `sub/` subdirectory behind and emitted PHPUnit warnings on every run

No behavior changes for well-formed input; 59 tests unchanged and passing.

---

## [1.4.1] - 2026-08-06

### Fixed
- `UniqueFieldScopeResolverInterface` now carries `#[AutoconfigureTag]`. Without it, the
  `AutowireLocator` in `UniqueFieldValidator` resolved to an empty locator — `AutowireLocator`'s
  string argument is a tag name, not an "implements" lookup, so implementing services never
  reached it. `scopeResolverClass` was unusable in 1.4.0.

## [1.4.0] - 2026-08-06

### Added
- `UniqueFieldScope::$scopeResolverClass` — delegates scope-entity resolution to an app-provided
  `UniqueFieldScopeResolverInterface` service instead of the built-in flat `findOneBy`. Use it when
  the scope entity is reachable only through more than one association hop, or needs any business
  logic beyond a plain lookup. Mutually exclusive with `scopeEntityClass`, only valid with
  `mode: Filter`.

---

## [1.3.0] - 2026-07-18

### Changed
- **Breaking:** `BuilderUrlClient` no longer reads a single `APP_CLIENT_URL` env var. It now takes `APP_CLIENT_URL_PROTOCOL`, `APP_CLIENT_URL_DOMAIN` (apex domain, without any subdomain) and `APP_CLIENT_URL_PORT` (empty string omits the port).

### Added
- `BuilderUrlClient::generate()` accepts an optional `$subdomain` argument, prepended to the domain (e.g. a tenant slug, or a fixed value like `"hub"`). `null` (default) builds the bare apex URL, preserving the previous single-tenant behavior.

### Migration
```dotenv
# Before
APP_CLIENT_URL=http://hub.ctr.lvh.me:8080

# After
APP_CLIENT_URL_PROTOCOL=http
APP_CLIENT_URL_DOMAIN=ctr.lvh.me
APP_CLIENT_URL_PORT=8080
```
```php
// Before
$this->builderUrlClient->generate($path, $params);

// After — apps without a subdomain concept
$this->builderUrlClient->generate($path, $params);

// After — apps with a subdomain concept (tenant slug, fixed "hub", etc.)
$this->builderUrlClient->generate($path, $params, subdomain: $slug);
```

---

## [1.2.0] - 2026-07-17

### Added
- `UniqueFieldScopeMode::ScalarFilter` — narrows a `UniqueField` lookup by a plain scalar/enum sibling property (`UniqueFieldScopeSource::PropertyPath`) on the same DTO, without requiring an associated entity (`scopeEntityClass`). Supports compound uniqueness checks across multiple scalar fields on the same entity (e.g. `taxId` unique per `type`) by adding one scope per field.

---

## [1.1.0] - 2026-07-17

### Changed
- **Breaking:** `UniqueField`, `UniqueFieldValidator` and related classes moved from `Letkode\CommonBundle\Attribute\Constraint` to `Letkode\CommonBundle\Attribute\Constraint\UniqueField` (co-located feature folder).
- **Breaking:** `UniqueField::$skipBySelfProperty`, `$skipRouteParamValue` and `$skipByRouteFieldProperty` removed in favor of a single `UniqueField::$scopes` array of `UniqueFieldScope`. Each scope resolves a value from a route param or a sibling property (`UniqueFieldScopeSource`), then either narrows the lookup via an association (`UniqueFieldScopeMode::Filter`, the old scoping gap) or excludes a match from the violation (`UniqueFieldScopeMode::Exclude`, replaces the old `skip*` params).

### Added
- `UniqueField` can now scope the uniqueness check to an associated entity (e.g. unique email *per tenant* instead of globally) via `UniqueFieldScope` with `mode: Filter` and `scopeEntityClass`.
- `UniqueFieldScope` supports resolving values from a sibling DTO property (`UniqueFieldScopeSource::PropertyPath`), not just route params.

### Migration
```php
// Before
#[UniqueField(entityClass: User::class, field: 'email', skipRouteParamValue: 'uuid')]

// After
#[UniqueField(
    entityClass: User::class,
    field: 'email',
    scopes: [new UniqueFieldScope('uuid', UniqueFieldScopeSource::RouteParam, 'uuid', UniqueFieldScopeMode::Exclude)],
)]
```

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

[Unreleased]: https://github.com/letkode/common-bundle/compare/1.1.0...HEAD
[1.1.0]: https://github.com/letkode/common-bundle/compare/1.0.3...1.1.0
[1.0.3]: https://github.com/letkode/common-bundle/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/letkode/common-bundle/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/letkode/common-bundle/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/letkode/common-bundle/releases/tag/1.0.0
