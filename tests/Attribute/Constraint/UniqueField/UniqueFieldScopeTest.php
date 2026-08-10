<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Tests\Attribute\Constraint\UniqueField;

use Letkode\CommonBundle\Attribute\Constraint\UniqueField\UniqueFieldScope;
use Letkode\CommonBundle\Attribute\Constraint\UniqueField\UniqueFieldScopeMode;
use Letkode\CommonBundle\Attribute\Constraint\UniqueField\UniqueFieldScopeSource;
use PHPUnit\Framework\TestCase;

final class UniqueFieldScopeTest extends TestCase
{
    public function testScopeResolverClassWithScopeEntityClassThrows(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('scopeResolverClass and scopeEntityClass are mutually exclusive');

        new UniqueFieldScope(
            'companyRelation',
            UniqueFieldScopeSource::RouteParam,
            'companyId',
            scopeEntityClass: \stdClass::class,
            scopeResolverClass: DummyScopeResolver::class,
        );
    }

    public function testScopeResolverClassWithExcludeModeThrows(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('scopeResolverClass is only valid with mode Filter');

        new UniqueFieldScope(
            'companyRelation',
            UniqueFieldScopeSource::RouteParam,
            'companyId',
            UniqueFieldScopeMode::Exclude,
            scopeResolverClass: DummyScopeResolver::class,
        );
    }

    public function testScopeResolverClassAloneIsValid(): void
    {
        $scope = new UniqueFieldScope(
            'companyRelation',
            UniqueFieldScopeSource::RouteParam,
            'companyId',
            scopeResolverClass: DummyScopeResolver::class,
        );

        self::assertSame(DummyScopeResolver::class, $scope->scopeResolverClass);
    }
}
