<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Attribute\Constraint\UniqueField;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Resolves a UniqueFieldScope's scope entity via app-specific logic instead of a flat
 * findOneBy($resolveByField) — for scopes reachable only through more than one association hop,
 * or that need a business-rule lookup (e.g. a repository method with its own invariants).
 * Referenced by UniqueFieldScope::$scopeResolverClass; UniqueFieldValidator resolves the
 * implementing service by its own class name via an AutowireLocator. #[AutoconfigureTag] is what
 * makes that automatic — Symfony auto-tags every autoconfigured implementor with this interface's
 * FQCN, which AutowireLocator's $services argument reads as a tag name (not an "implements"
 * lookup), and indexes each entry by service id (= the concrete class's FQCN under standard
 * autowire/autoconfigure) — the same string consumers pass as scopeResolverClass. No manual
 * tagging needed as long as the implementing service is autowired/autoconfigured.
 */
#[AutoconfigureTag]
interface UniqueFieldScopeResolverInterface
{
    /**
     * @return object|null the scope entity to compare against the scope's entityField, or null to
     *                     skip validation entirely (treated as a pass, same contract as an
     *                     unresolved scopeEntityClass lookup)
     */
    public function resolve(mixed $rawValue): object|null;
}
