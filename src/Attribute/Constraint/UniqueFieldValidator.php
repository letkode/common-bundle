<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Attribute\Constraint;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueFieldValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly RequestStack $requestStack
    )
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueField) {
            throw new UnexpectedTypeException($constraint, UniqueField::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $em = null !== $constraint->em
            ? $this->registry->getManager($constraint->em)
            : $this->registry->getManagerForClass($constraint->entityClass);

        if (null === $em) {
            throw new \LogicException(\sprintf('No entity manager found for class "%s". Pass the "em" option explicitly.', $constraint->entityClass));
        }

        $existing = $em->getRepository($constraint->entityClass)
            ->findOneBy([$constraint->field => $value]);

        if (null === $existing) {
            return;
        }

        if (null !== $constraint->skipBySelfProperty) {
            $object = $this->context->getObject();
            if (null !== $object) {
                $selfId = new \ReflectionProperty($object, $constraint->skipBySelfProperty)->getValue($object);
                if (null !== $selfId) {
                    $ids = $em->getClassMetadata($constraint->entityClass)->getIdentifierValues($existing);
                    if (\in_array($selfId, $ids, strict: true)) {
                        return;
                    }
                }
            }
        }

        if (null !== $constraint->skipRouteParamValue) {
            $request = $this->requestStack->getCurrentRequest();
            $routeValue = $request?->attributes->get($constraint->skipRouteParamValue);
            if (null !== $routeValue) {
                $entityValue = (new \ReflectionProperty($existing, $constraint->skipByRouteFieldProperty))->getValue($existing);
                if (null !== $entityValue && (string) $entityValue === (string) $routeValue) {
                    return;
                }
            }
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ field }}', $constraint->field)
            ->setParameter('{{ value }}', (string) $value)
            ->addViolation();
    }
}
