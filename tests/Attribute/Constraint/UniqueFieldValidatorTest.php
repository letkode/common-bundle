<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Tests\Attribute\Constraint;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Letkode\CommonBundle\Attribute\Constraint\UniqueField;
use Letkode\CommonBundle\Attribute\Constraint\UniqueFieldValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class UniqueFieldValidatorTest extends TestCase
{
    private ManagerRegistry&MockObject $registry;
    private RequestStack&MockObject $requestStack;
    private ExecutionContextInterface&MockObject $context;
    private UniqueFieldValidator $validator;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new UniqueFieldValidator($this->registry, $this->requestStack);
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
        $em->method('getClassMetadata')->willReturn($this->mockMetadata([]));
        $this->registry->method('getManagerForClass')->willReturn($em);

        $this->context->method('buildViolation')->willReturn($this->mockViolationBuilder(expectAddViolation: true));

        $this->validator->validate('test@example.com', new UniqueField(entityClass: \stdClass::class, field: 'email'));
    }

    public function testIgnorePropertySkipsViolationWhenIdMatches(): void
    {
        $existing = new \stdClass();
        $em = $this->mockEm(\stdClass::class, $existing);
        $em->method('getClassMetadata')->willReturn($this->mockMetadata(['id' => 42]));
        $this->registry->method('getManagerForClass')->willReturn($em);

        $dto = new class { public int $id = 42; };
        $this->context->method('getObject')->willReturn($dto);
        $this->context->expects(self::never())->method('buildViolation');

        $this->validator->validate(
            'test@example.com',
            new UniqueField(entityClass: \stdClass::class, field: 'email', ignoreProperty: 'id'),
        );
    }

    public function testIgnorePropertyAddsViolationWhenIdDiffers(): void
    {
        $existing = new \stdClass();
        $em = $this->mockEm(\stdClass::class, $existing);
        $em->method('getClassMetadata')->willReturn($this->mockMetadata(['id' => 99]));
        $this->registry->method('getManagerForClass')->willReturn($em);

        $dto = new class { public int $id = 42; };
        $this->context->method('getObject')->willReturn($dto);
        $this->context->method('buildViolation')->willReturn($this->mockViolationBuilder(expectAddViolation: true));

        $this->validator->validate(
            'test@example.com',
            new UniqueField(entityClass: \stdClass::class, field: 'email', ignoreProperty: 'id'),
        );
    }

    public function testIgnoreRouteParamSkipsViolationWhenRouteValueMatchesId(): void
    {
        $existing = new \stdClass();
        $em = $this->mockEm(\stdClass::class, $existing);
        $em->method('getClassMetadata')->willReturn($this->mockMetadata(['uuid' => 'abc-123']));
        $this->registry->method('getManagerForClass')->willReturn($em);

        $request = Request::create('/');
        $request->attributes->set('uuid', 'abc-123');
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->context->expects(self::never())->method('buildViolation');

        $this->validator->validate(
            'test@example.com',
            new UniqueField(entityClass: \stdClass::class, field: 'email', ignoreRouteParam: 'uuid'),
        );
    }

    public function testIgnoreRouteParamAddsViolationWhenRouteValueDiffers(): void
    {
        $existing = new \stdClass();
        $em = $this->mockEm(\stdClass::class, $existing);
        $em->method('getClassMetadata')->willReturn($this->mockMetadata(['uuid' => 'abc-123']));
        $this->registry->method('getManagerForClass')->willReturn($em);

        $request = Request::create('/');
        $request->attributes->set('uuid', 'different-uuid');
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->context->method('buildViolation')->willReturn($this->mockViolationBuilder(expectAddViolation: true));

        $this->validator->validate(
            'test@example.com',
            new UniqueField(entityClass: \stdClass::class, field: 'email', ignoreRouteParam: 'uuid'),
        );
    }

    public function testIgnoreRouteParamAddsViolationWhenNoCurrentRequest(): void
    {
        $existing = new \stdClass();
        $em = $this->mockEm(\stdClass::class, $existing);
        $em->method('getClassMetadata')->willReturn($this->mockMetadata(['uuid' => 'abc-123']));
        $this->registry->method('getManagerForClass')->willReturn($em);

        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $this->context->method('buildViolation')->willReturn($this->mockViolationBuilder(expectAddViolation: true));

        $this->validator->validate(
            'test@example.com',
            new UniqueField(entityClass: \stdClass::class, field: 'email', ignoreRouteParam: 'uuid'),
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

    /** @param array<string, mixed> $ids */
    private function mockMetadata(array $ids): ClassMetadata&MockObject
    {
        $meta = $this->createMock(ClassMetadata::class);
        $meta->method('getIdentifierValues')->willReturn($ids);

        return $meta;
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
