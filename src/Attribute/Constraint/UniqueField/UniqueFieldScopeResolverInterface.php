<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Attribute\Constraint\UniqueField;

/**
 * Resolves a UniqueFieldScope's scope entity via app-specific logic instead of a flat
 * findOneBy($resolveByField) — for scopes reachable only through more than one association hop,
 * or that need a business-rule lookup (e.g. a repository method with its own invariants).
 * Referenced by UniqueFieldScope::$scopeResolverClass; UniqueFieldValidator resolves the
 * implementing service by its own class name via an AutowireLocator, so no manual tagging is
 * needed as long as the service is autowired/autoconfigured.
 */
interface UniqueFieldScopeResolverInterface
{
    /**
     * @return object|null the scope entity to compare against the scope's entityField, or null to
     *                     skip validation entirely (treated as a pass, same contract as an
     *                     unresolved scopeEntityClass lookup)
     */
    public function resolve(mixed $rawValue): object|null;
}
