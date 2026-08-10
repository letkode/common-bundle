<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Tests\Attribute\Constraint\UniqueField;

use Letkode\CommonBundle\Attribute\Constraint\UniqueField\UniqueFieldScopeResolverInterface;

final class DummyScopeResolver implements UniqueFieldScopeResolverInterface
{
    public function resolve(mixed $rawValue): object|null
    {
        return null;
    }
}
