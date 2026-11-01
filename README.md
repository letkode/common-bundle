# letkode/common-bundle

Common exceptions, attributes, value resolvers and utilities for Symfony applications.

---

## Installation

```bash
composer require letkode/common-bundle
```

Symfony Flex will register the bundle automatically. If not using Flex, add it manually:

```php
// config/bundles.php
return [
    Letkode\CommonBundle\LetkodeCommonBundle::class => ['all' => true],
];
```

---

## Contents

### Exceptions

`ValueObjectException` is thrown by value objects that reject invalid input. It carries a translation key and its parameters so the caller can translate the message in the `validators` domain.

```php
throw new ValueObjectException('Invalid email.', translationKey: 'errors.email_invalid');
```

The HTTP status exceptions (`BadRequestException`, `NotFoundException`, ...) and the JSON `ExceptionListener` now live in `letkode/http-exception-bundle`.

### Attributes

#### `#[UniqueField]` — Constraint

Validates that a field value is unique in the database via Doctrine.

```php
#[UniqueField(entityClass: User::class, field: 'email')]
public string $email;
```

Use `scopes` to narrow the lookup and/or exclude the record being edited from the check. Each
`UniqueFieldScope` resolves a value from a route param or a sibling property, then either
`Filter`s the query (via an association) or `Exclude`s a match from the violation:

```php
// Update DTO: same email is fine if it's still the record at /users/{uuid}
#[UniqueField(
    entityClass: User::class,
    field: 'email',
    scopes: [new UniqueFieldScope('uuid', UniqueFieldScopeSource::RouteParam, 'uuid', UniqueFieldScopeMode::Exclude)],
)]
public string $email;

// Create DTO: email only needs to be unique within the tenant from /tenants/{tenantUuid}/contacts
#[UniqueField(
    entityClass: TenantContact::class,
    field: 'email',
    scopes: [new UniqueFieldScope('tenant', UniqueFieldScopeSource::RouteParam, 'tenantUuid', scopeEntityClass: Tenant::class)],
)]
public string $email;
```

Use `valueTransformer` to compare the value in the same form it is stored in. Implement
`UniqueFieldValueTransformerInterface` in an autowired/autoconfigured service and reference its
class; the validator transforms the value before the lookup and reports the transformed value in
the violation. A transformer returning `null` or `''` skips the check.

```php
final class LowercaseTransformer implements UniqueFieldValueTransformerInterface
{
    public function transform(mixed $value, object|null $object, string|null $property): mixed
    {
        return \is_string($value) ? mb_strtolower(trim($value)) : $value;
    }
}

#[UniqueField(entityClass: User::class, field: 'email', valueTransformer: LowercaseTransformer::class)]
public string $email;
```

Transformers must be idempotent (transforming an already-transformed value returns it unchanged).
Scopes keep reading the original sibling values.

#### `#[MapUuid]` — Mapping

Marks a constructor parameter or property for automatic UUID deserialization.

### Value Resolver

#### `UuidValueResolver`

Automatically resolves `Uuid` typed route parameters without manual conversion.

```php
#[Route('/users/{uuid}')]
public function show(Uuid $uuid): JsonResponse { ... }
```

### OpenAPI

#### `DocAttributeRouteDescriber`

Optional: registered only when [`nelmio/api-doc-bundle`](https://github.com/nelmio/NelmioApiDocBundle) `^5.10` is installed.

NelmioApiDocBundle only reads `#[OA\*]` attributes declared on the controller class or method itself. To keep the
documentation apart from the controller, declare an attribute class that carries the operations and apply it to the
controller; the describer merges them into the generated specification:

```php
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
#[OA\Get(path: '/api/companies', summary: 'List companies', responses: [new OA\Response(response: 200, description: 'OK')])]
#[OA\Post(path: '/api/companies', summary: 'Create company')]
final class DocCompaniesEndpoints {}

#[Route('/api/companies')]
#[DocCompaniesEndpoints]
final class CompanyController { /* ... */ }
```

- Without the describer Nelmio instantiates `#[DocCompaniesEndpoints]` and ignores it: none of its operations reach the specification.
- An operation applies to a route when its HTTP method matches and, if it declares a `path`, the path matches the route's.
  Place the attribute on a method to document just that route (an operation without `path` then applies to it).
- The Doc takes precedence over what Nelmio generated: `tags` are united, `parameters` and `responses` are matched by
  (`in`, `name`) and by status code, and any other property the Doc defines (summary, description, request body...) replaces it.
  A parameter generated from `#[MapQueryString]` therefore keeps its own entry unless the Doc declares one with the same name,
  which is how a Doc can add e.g. an `enum` to `sort`.
- Only operations (`OA\Get`, `OA\Post`, ...) are applied; a root `OA\Response`, `OA\Parameter` or `OA\RequestBody` on a Doc class is ignored.

### Utils

#### `BuilderUrlClient`

Builds absolute URLs using the configured `APP_CLIENT_URL` base. Requires `letkode/helpers-bundle`.

#### `JsonReader`

Reads and decodes JSON files from the filesystem, throwing on invalid JSON.

---

## Requirements

- PHP `^8.4`
- Symfony `^7.0 || ^8.0`
- `doctrine/persistence` `^3.0`
- `letkode/helpers-bundle` `^1.0`

---

## License

MIT — see [LICENSE](LICENSE).
