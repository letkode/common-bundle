<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Attribute\Constraint\UniqueField;

/**
 * One resolution rule for a UniqueField check: read a value from $source (by $key), then either
 * narrow the lookup via an association named $entityField pointing to $scopeEntityClass (Filter),
 * narrow the lookup via a plain scalar criterion on $entityField (ScalarFilter), or exclude a
 * match from the violation by comparing $entityField on the found record against the resolved
 * value (Exclude).
 */
final readonly class UniqueFieldScope
{
    /** @param class-string|null $scopeEntityClass required when $mode is Filter */
    public function __construct(
        public string $entityField,
        public UniqueFieldScopeSource $source,
        public string $key,
        public UniqueFieldScopeMode $mode = UniqueFieldScopeMode::Filter,
        public string|null $scopeEntityClass = null,
        public string $resolveByField = 'uuid',
    ) {
    }
}
