<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Attribute\Constraint\UniqueField;

use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueFieldValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly RequestStack $requestStack,
        #[AutowireLocator(UniqueFieldScopeResolverInterface::class)]
        private readonly ContainerInterface $scopeResolvers,
        #[AutowireLocator(UniqueFieldValueTransformerInterface::class)]
        private readonly ContainerInterface|null $valueTransformers = null,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueField) {
            throw new UnexpectedTypeException($constraint, UniqueField::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (null !== $constraint->valueTransformer) {
            $value = $this->transformValue($value, $constraint->valueTransformer);

            if (null === $value || '' === $value) {
                return;
            }
        }

        $em = null !== $constraint->em
            ? $this->registry->getManager($constraint->em)
            : $this->registry->getManagerForClass($constraint->entityClass);

        if (null === $em) {
            throw new \LogicException(\sprintf('No entity manager found for class "%s". Pass the "em" option explicitly.', $constraint->entityClass));
        }

        $criteria = [$constraint->field => $value];

        /** @var list<array{0: string, 1: string}> $excludes */
        $excludes = [];

        foreach ($constraint->scopes as $scope) {
            $rawValue = $this->resolveScopeValue($scope);

            if (null === $rawValue) {
                continue;
            }

            if (UniqueFieldScopeMode::Exclude === $scope->mode) {
                $excludes[] = [$scope->entityField, \is_scalar($rawValue) || $rawValue instanceof \Stringable ? (string) $rawValue : ''];

                continue;
            }

            if (UniqueFieldScopeMode::ScalarFilter === $scope->mode) {
                $criteria[$scope->entityField] = $rawValue;

                continue;
            }

            if (null !== $scope->scopeResolverClass) {
                $resolver = $this->scopeResolvers->get($scope->scopeResolverClass);

                if (!$resolver instanceof UniqueFieldScopeResolverInterface) {
                    throw new \LogicException(\sprintf('Service "%s" must implement %s.', $scope->scopeResolverClass, UniqueFieldScopeResolverInterface::class));
                }

                $scopeEntity = $resolver->resolve($rawValue);

                if (null === $scopeEntity) {
                    return;
                }

                $criteria[$scope->entityField] = $scopeEntity;

                continue;
            }

            if (null === $scope->scopeEntityClass) {
                throw new \LogicException(\sprintf('UniqueFieldScope for "%s" requires scopeEntityClass when mode is Filter.', $scope->entityField));
            }

            $scopeEntity = $em->getRepository($scope->scopeEntityClass)->findOneBy([$scope->resolveByField => $rawValue]);

            if (null === $scopeEntity) {
                return;
            }

            $criteria[$scope->entityField] = $scopeEntity;
        }

        $existing = $em->getRepository($constraint->entityClass)->findOneBy($criteria);

        if (null === $existing) {
            return;
        }

        foreach ($excludes as [$entityField, $expected]) {
            $actual = new \ReflectionProperty($existing, $entityField)->getValue($existing);

            if (null !== $actual && (\is_scalar($actual) || $actual instanceof \Stringable) && (string) $actual === $expected) {
                return;
            }
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ field }}', $constraint->field)
            ->setParameter('{{ value }}', \is_scalar($value) || $value instanceof \Stringable ? (string) $value : \gettype($value))
            ->addViolation();
    }

    private function transformValue(mixed $value, string $transformerClass): mixed
    {
        if (null === $this->valueTransformers || !$this->valueTransformers->has($transformerClass)) {
            throw new \LogicException(\sprintf('Value transformer "%s" is not a registered service. Make sure it is autowired and autoconfigured and implements %s.', $transformerClass, UniqueFieldValueTransformerInterface::class));
        }

        $transformer = $this->valueTransformers->get($transformerClass);

        if (!$transformer instanceof UniqueFieldValueTransformerInterface) {
            throw new \LogicException(\sprintf('Service "%s" must implement %s.', $transformerClass, UniqueFieldValueTransformerInterface::class));
        }

        return $transformer->transform($value, $this->context->getObject(), $this->context->getPropertyName());
    }

    private function resolveScopeValue(UniqueFieldScope $scope): mixed
    {
        return match ($scope->source) {
            UniqueFieldScopeSource::RouteParam => $this->requestStack->getCurrentRequest()?->attributes->get($scope->key),
            UniqueFieldScopeSource::PropertyPath => $this->resolvePropertyValue($scope->key),
        };
    }

    private function resolvePropertyValue(string $property): mixed
    {
        $object = $this->context->getObject();

        if (null === $object || !property_exists($object, $property)) {
            return null;
        }

        return new \ReflectionProperty($object, $property)->getValue($object);
    }
}
