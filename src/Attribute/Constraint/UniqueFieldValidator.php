<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Attribute\Constraint;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueFieldValidator extends ConstraintValidator
{
    public function __construct(private readonly ManagerRegistry $registry)
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

        if (null !== $constraint->ignoreProperty) {
            $object = $this->context->getObject();

            if (null !== $object) {
                $ignoreId = new \ReflectionProperty($object, $constraint->ignoreProperty)->getValue($object);

                if (null !== $ignoreId) {
                    $ids = $em->getClassMetadata($constraint->entityClass)->getIdentifierValues($existing);

                    if (\in_array($ignoreId, $ids, strict: true)) {
                        return;
                    }
                }
            }
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ field }}', $constraint->field)
            ->setParameter('{{ value }}', (string) $value)
            ->addViolation();
    }
}
