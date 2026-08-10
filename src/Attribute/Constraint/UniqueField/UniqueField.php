<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Attribute\Constraint\UniqueField;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class UniqueField extends Constraint
{
    /**
     * @param class-string       $entityClass
     * @param UniqueFieldScope[] $scopes
     */
    public function __construct(
        public readonly string $entityClass,
        public readonly string $field,
        public readonly string $message = 'unique_field.not_unique',
        public readonly string|null $em = null,
        public readonly array $scopes = [],
        array|null $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(groups: $groups, payload: $payload);
    }
}
