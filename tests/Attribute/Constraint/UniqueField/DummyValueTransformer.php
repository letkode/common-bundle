<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Tests\Attribute\Constraint\UniqueField;

use Letkode\CommonBundle\Attribute\Constraint\UniqueField\UniqueFieldValueTransformerInterface;

final class DummyValueTransformer implements UniqueFieldValueTransformerInterface
{
    public function transform(mixed $value, object|null $object, string|null $property): mixed
    {
        return \is_string($value) ? mb_strtolower($value) : $value;
    }
}
