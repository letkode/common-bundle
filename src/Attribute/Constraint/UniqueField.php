<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Attribute\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class UniqueField extends Constraint
{
    public function __construct(
        public readonly string $entityClass,
        public readonly string $field,
        public readonly string $message = 'unique_field.not_unique',
        public readonly string|null $em = null,
        public readonly string|null $ignoreProperty = null,
        public readonly string|null $ignoreRouteParam = null,
        array|null $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(groups: $groups, payload: $payload);
    }
}
