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

All exceptions implement `HttpStatusExceptionInterface` and map directly to an HTTP status code. Throw them from services — the `ExceptionListener` in your project converts them to JSON responses.

| Class | HTTP |
|---|---|
| `BadRequestException` | 400 |
| `UnauthorizedException` | 401 |
| `EntityNotFoundException` | 404 |
| `TooManyRequestsException` | 429 |
| `ValueObjectException` | 422 |

```php
throw new EntityNotFoundException('User not found.');
throw new ValueObjectException('Invalid email.', translationKey: 'errors.email_invalid');
```

### Attributes

#### `#[UniqueField]` — Constraint

Validates that a field value is unique in the database via Doctrine.

```php
#[UniqueField(entity: User::class, field: 'email')]
public string $email;
```

#### `#[MapUuid]` — Mapping

Marks a constructor parameter or property for automatic UUID deserialization.

### Value Resolver

#### `UuidValueResolver`

Automatically resolves `Uuid` typed route parameters without manual conversion.

```php
#[Route('/users/{uuid}')]
public function show(Uuid $uuid): JsonResponse { ... }
```

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
