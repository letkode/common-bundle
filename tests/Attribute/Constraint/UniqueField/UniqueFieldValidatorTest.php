<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Tests\Attribute\Constraint\UniqueField;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Letkode\CommonBundle\Attribute\Constraint\UniqueField\UniqueField;
use Letkode\CommonBundle\Attribute\Constraint\UniqueField\UniqueFieldScope;
use Letkode\CommonBundle\Attribute\Constraint\UniqueField\UniqueFieldScopeMode;
use Letkode\CommonBundle\Attribute\Constraint\UniqueField\UniqueFieldScopeResolverInterface;
use Letkode\CommonBundle\Attribute\Constraint\UniqueField\UniqueFieldScopeSource;
use Letkode\CommonBundle\Attribute\Constraint\UniqueField\UniqueFieldValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class UniqueFieldValidatorTest extends TestCase
{
    private ManagerRegistry&MockObject $registry;
    private RequestStack&MockObject $requestStack;
    private ContainerInterface&MockObject $scopeResolvers;
    private ExecutionContextInterface&MockObject $context;
    private UniqueFieldValidator $validator;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->scopeResolvers = $this->createMock(ContainerInterface::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new UniqueFieldValidator($this->registry, $this->requestStack, $this->scopeResolvers);
        $this->validator->initialize($this->context);
    }

    public function testSkipsOnNullValue(): void
    {
        $this->registry->expects(self::never())->method('getManagerForClass');

        $this->validator->validate(null, new UniqueField(entityClass: \stdClass::class, field: 'email'));
    }

    public function testSkipsOnEmptyString(): void
    {
        $this->registry->expects(self::never())->method('getManagerForClass');

        $this->validator->validate('', new UniqueField(entityClass: \stdClass::class, field: 'email'));
    }

    public function testNoViolationWhenNoExistingRecord(): void
    {
        $em = $this->mockEm(\stdClass::class, null);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $this->context->expects(self::never())->method('buildViolation');

        $this->validator->validate('test@example.com', new UniqueField(entityClass: \stdClass::class, field: 'email'));
    }

    public function testAddsViolationWhenDuplicateFound(): void
    {
        $existing = new \stdClass();
        $em = $this->mockEm(\stdClass::class, $existing);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $this->context->method('buildViolation')->willReturn($this->mockViolationBuilder(expectAddViolation: true));

        $this->validator->validate('test@example.com', new UniqueField(entityClass: \stdClass::class, field: 'email'));
    }

    public function testExcludePropertyPathSkipsViolationWhenValueMatches(): void
    {
        $existing = new class {
            public int $id = 42;
        };
        $em = $this->mockEm($existing::class, $existing);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $dto = new class {
            public int $id = 42;
        };
        $this->context->method('getObject')->willReturn($dto);
        $this->context->expects(self::never())->method('buildViolation');

        $this->validator->validate(
            'test@example.com',
            new UniqueField(
                entityClass: $existing::class,
                field: 'email',
                scopes: [new UniqueFieldScope('id', UniqueFieldScopeSource::PropertyPath, 'id', UniqueFieldScopeMode::Exclude)],
            ),
        );
    }

    public function testExcludePropertyPathAddsViolationWhenValueDiffers(): void
    {
        $existing = new class {
            public int $id = 99;
        };
        $em = $this->mockEm($existing::class, $existing);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $dto = new class {
            public int $id = 42;
        };
        $this->context->method('getObject')->willReturn($dto);
        $this->context->method('buildViolation')->willReturn($this->mockViolationBuilder(expectAddViolation: true));

        $this->validator->validate(
            'test@example.com',
            new UniqueField(
                entityClass: $existing::class,
                field: 'email',
                scopes: [new UniqueFieldScope('id', UniqueFieldScopeSource::PropertyPath, 'id', UniqueFieldScopeMode::Exclude)],
            ),
        );
    }

    public function testExcludeRouteParamSkipsViolationWhenEntityFieldMatchesRoute(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $existing = new class {
            public string $uuid = '550e8400-e29b-41d4-a716-446655440000';
        };
        $em = $this->mockEm($existing::class, $existing);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $request = Request::create('/');
        $request->attributes->set('uuid', $uuid);
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->context->expects(self::never())->method('buildViolation');

        $this->validator->validate(
            'test@example.com',
            new UniqueField(
                entityClass: $existing::class,
                field: 'email',
                scopes: [new UniqueFieldScope('uuid', UniqueFieldScopeSource::RouteParam, 'uuid', UniqueFieldScopeMode::Exclude)],
            ),
        );
    }

    public function testExcludeRouteParamAddsViolationWhenEntityFieldDiffersFromRoute(): void
    {
        $existing = new class {
            public string $uuid = 'abc-123';
        };
        $em = $this->mockEm($existing::class, $existing);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $request = Request::create('/');
        $request->attributes->set('uuid', 'different-uuid');
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->context->method('buildViolation')->willReturn($this->mockViolationBuilder(expectAddViolation: true));

        $this->validator->validate(
            'test@example.com',
            new UniqueField(
                entityClass: $existing::class,
                field: 'email',
                scopes: [new UniqueFieldScope('uuid', UniqueFieldScopeSource::RouteParam, 'uuid', UniqueFieldScopeMode::Exclude)],
            ),
        );
    }

    public function testExcludeRouteParamUsesCustomEntityField(): void
    {
        $existing = new class {
            public string $slug = 'my-slug';
        };
        $em = $this->mockEm($existing::class, $existing);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $request = Request::create('/');
        $request->attributes->set('slug', 'my-slug');
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->context->expects(self::never())->method('buildViolation');

        $this->validator->validate(
            'test@example.com',
            new UniqueField(
                entityClass: $existing::class,
                field: 'email',
                scopes: [new UniqueFieldScope('slug', UniqueFieldScopeSource::RouteParam, 'slug', UniqueFieldScopeMode::Exclude)],
            ),
        );
    }

    public function testExcludeRouteParamAddsViolationWhenNoCurrentRequest(): void
    {
        $existing = new class {
            public string $uuid = 'abc-123';
        };
        $em = $this->mockEm($existing::class, $existing);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $this->context->method('buildViolation')->willReturn($this->mockViolationBuilder(expectAddViolation: true));

        $this->validator->validate(
            'test@example.com',
            new UniqueField(
                entityClass: $existing::class,
                field: 'email',
                scopes: [new UniqueFieldScope('uuid', UniqueFieldScopeSource::RouteParam, 'uuid', UniqueFieldScopeMode::Exclude)],
            ),
        );
    }

    public function testFilterScopeNarrowsLookupByAssociatedEntity(): void
    {
        $tenant = new class {
            public string $uuid = 'tenant-uuid';
        };
        $existing = new \stdClass();

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'test@example.com', 'tenant' => $tenant])
            ->willReturn($existing);

        $tenantRepo = $this->createMock(ObjectRepository::class);
        $tenantRepo->method('findOneBy')->with(['uuid' => 'tenant-uuid'])->willReturn($tenant);

        $em = $this->createMock(ObjectManager::class);
        $em->method('getRepository')->willReturnMap([
            [\stdClass::class, $repo],
            [$tenant::class, $tenantRepo],
        ]);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $request = Request::create('/');
        $request->attributes->set('tenantUuid', 'tenant-uuid');
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->context->method('buildViolation')->willReturn($this->mockViolationBuilder(expectAddViolation: true));

        $this->validator->validate(
            'test@example.com',
            new UniqueField(
                entityClass: \stdClass::class,
                field: 'email',
                scopes: [new UniqueFieldScope('tenant', UniqueFieldScopeSource::RouteParam, 'tenantUuid', scopeEntityClass: $tenant::class)],
            ),
        );
    }

    public function testFilterScopeSkipsWhenScopeEntityNotFound(): void
    {
        $tenantEntityClass = new class {
            public string $uuid = '';
        }::class;

        $tenantRepo = $this->createMock(ObjectRepository::class);
        $tenantRepo->method('findOneBy')->willReturn(null);

        $em = $this->createMock(ObjectManager::class);
        $em->method('getRepository')->with($tenantEntityClass)->willReturn($tenantRepo);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $request = Request::create('/');
        $request->attributes->set('tenantUuid', 'missing-tenant');
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->context->expects(self::never())->method('buildViolation');

        $this->validator->validate(
            'test@example.com',
            new UniqueField(
                entityClass: \stdClass::class,
                field: 'email',
                scopes: [new UniqueFieldScope('tenant', UniqueFieldScopeSource::RouteParam, 'tenantUuid', scopeEntityClass: $tenantEntityClass)],
            ),
        );
    }

    public function testScalarFilterScopeNarrowsLookupBySiblingProperty(): void
    {
        $dto = new class {
            public string $taxId = '20123456789';
            public string $type = 'ruc';
        };
        $existing = new \stdClass();

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['taxId' => '20123456789', 'type' => 'ruc'])
            ->willReturn($existing);

        $em = $this->createMock(ObjectManager::class);
        $em->method('getRepository')->with(\stdClass::class)->willReturn($repo);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $this->context->method('getObject')->willReturn($dto);
        $this->context->method('buildViolation')->willReturn($this->mockViolationBuilder(expectAddViolation: true));

        $this->validator->validate(
            $dto->taxId,
            new UniqueField(
                entityClass: \stdClass::class,
                field: 'taxId',
                scopes: [new UniqueFieldScope('type', UniqueFieldScopeSource::PropertyPath, 'type', UniqueFieldScopeMode::ScalarFilter)],
            ),
        );
    }

    public function testScalarFilterScopeSkipsViolationWhenNoMatchUnderCriteria(): void
    {
        $dto = new class {
            public string $taxId = '20123456789';
            public string $type = 'dni';
        };

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['taxId' => '20123456789', 'type' => 'dni'])
            ->willReturn(null);

        $em = $this->createMock(ObjectManager::class);
        $em->method('getRepository')->with(\stdClass::class)->willReturn($repo);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $this->context->method('getObject')->willReturn($dto);
        $this->context->expects(self::never())->method('buildViolation');

        $this->validator->validate(
            $dto->taxId,
            new UniqueField(
                entityClass: \stdClass::class,
                field: 'taxId',
                scopes: [new UniqueFieldScope('type', UniqueFieldScopeSource::PropertyPath, 'type', UniqueFieldScopeMode::ScalarFilter)],
            ),
        );
    }

    public function testScopeResolverClassNarrowsLookupByResolvedEntity(): void
    {
        $relation = new class {
            public string $id = 'relation-1';
        };
        $existing = new \stdClass();

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'test@example.com', 'companyRelation' => $relation])
            ->willReturn($existing);

        $em = $this->createMock(ObjectManager::class);
        $em->method('getRepository')->with(\stdClass::class)->willReturn($repo);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $resolver = $this->createMock(UniqueFieldScopeResolverInterface::class);
        $resolver->method('resolve')->with('company-uuid')->willReturn($relation);
        $this->scopeResolvers->method('get')->with('SomeResolverClass')->willReturn($resolver);

        $request = Request::create('/');
        $request->attributes->set('companyId', 'company-uuid');
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->context->method('buildViolation')->willReturn($this->mockViolationBuilder(expectAddViolation: true));

        $this->validator->validate(
            'test@example.com',
            new UniqueField(
                entityClass: \stdClass::class,
                field: 'email',
                scopes: [new UniqueFieldScope(
                    'companyRelation',
                    UniqueFieldScopeSource::RouteParam,
                    'companyId',
                    scopeResolverClass: 'SomeResolverClass',
                )],
            ),
        );
    }

    public function testScopeResolverClassSkipsWhenResolverReturnsNull(): void
    {
        $em = $this->mockEm(\stdClass::class, null);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $resolver = $this->createMock(UniqueFieldScopeResolverInterface::class);
        $resolver->method('resolve')->willReturn(null);
        $this->scopeResolvers->method('get')->with('SomeResolverClass')->willReturn($resolver);

        $request = Request::create('/');
        $request->attributes->set('companyId', 'missing-company');
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->context->expects(self::never())->method('buildViolation');

        $this->validator->validate(
            'test@example.com',
            new UniqueField(
                entityClass: \stdClass::class,
                field: 'email',
                scopes: [new UniqueFieldScope(
                    'companyRelation',
                    UniqueFieldScopeSource::RouteParam,
                    'companyId',
                    scopeResolverClass: 'SomeResolverClass',
                )],
            ),
        );
    }

    private function mockEm(string $class, mixed $findResult): ObjectManager&MockObject
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->method('findOneBy')->willReturn($findResult);

        $em = $this->createMock(ObjectManager::class);
        $em->method('getRepository')->with($class)->willReturn($repo);

        return $em;
    }

    private function mockViolationBuilder(bool $expectAddViolation): ConstraintViolationBuilderInterface&MockObject
    {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->method('setParameter')->willReturnSelf();
        if ($expectAddViolation) {
            $builder->expects(self::once())->method('addViolation');
        }

        return $builder;
    }
}
