<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Attribute\Constraint\UniqueField;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Transforms the value a UniqueField checks before it is looked up — e.g. to compare an input in
 * the same canonical form it is stored in (lower-cased email, stripped separators, ...).
 * Referenced by UniqueField::$valueTransformer; UniqueFieldValidator resolves the implementing
 * service by its own class name via an AutowireLocator, the same way as
 * UniqueFieldScopeResolverInterface (#[AutoconfigureTag] tags every autoconfigured implementor).
 *
 * Implementations must be idempotent: transforming an already-transformed value returns it as is.
 */
#[AutoconfigureTag]
interface UniqueFieldValueTransformerInterface
{
    /**
     * @param object|null $object   the object being validated (null when validating a bare value)
     * @param string|null $property the property being validated (null when validating a bare value)
     *
     * @return mixed the value to look up; null or '' skips the check (treated as a pass)
     */
    public function transform(mixed $value, object|null $object, string|null $property): mixed;
}
