<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Tests\Utils;

use Letkode\CommonBundle\Utils\BuilderUrlClient;
use Letkode\HelpersBundle\String\ReplaceValuesTextFromArrayHelper;
use PHPUnit\Framework\TestCase;

final class BuilderUrlClientTest extends TestCase
{
    private BuilderUrlClient $builder;

    protected function setUp(): void
    {
        $this->builder = new BuilderUrlClient(
            protocol: 'https',
            domain: 'app.example.com',
            port: '',
            replaceValuesHelper: new ReplaceValuesTextFromArrayHelper(),
        );
    }

    // --- Path without placeholders ---

    public function testGeneratesSimpleUrl(): void
    {
        $url = $this->builder->generate('/dashboard');

        $this->assertSame('https://app.example.com/dashboard', $url);
    }

    public function testAddsSlashWhenPathHasNoLeadingSlash(): void
    {
        $url = $this->builder->generate('dashboard');

        $this->assertSame('https://app.example.com/dashboard', $url);
    }

    // --- Placeholder replacement ---

    public function testReplacesPlaceholderInPath(): void
    {
        $url = $this->builder->generate('/invite/#[token]#', ['token' => 'abc123']);

        $this->assertSame('https://app.example.com/invite/abc123', $url);
    }

    public function testReplacesMultiplePlaceholders(): void
    {
        $url = $this->builder->generate('/org/#[org]#/user/#[id]#', ['org' => 'acme', 'id' => '42']);

        $this->assertSame('https://app.example.com/org/acme/user/42', $url);
    }

    // --- Extra params as query string ---

    public function testAppendsUnusedParamsAsQueryString(): void
    {
        $url = $this->builder->generate('/invite/#[token]#', ['token' => 'abc123', 'lang' => 'es']);

        $this->assertSame('https://app.example.com/invite/abc123?lang=es', $url);
    }

    public function testAppendsQueryStringWithNoPlaceholders(): void
    {
        $url = $this->builder->generate('/users', ['page' => 2, 'limit' => 10]);

        $this->assertSame('https://app.example.com/users?page=2&limit=10', $url);
    }

    public function testDoesNotAppendQueryStringWhenAllParamsUsed(): void
    {
        $url = $this->builder->generate('/reset/#[token]#', ['token' => 'xyz']);

        $this->assertSame('https://app.example.com/reset/xyz', $url);
    }

    // --- Empty parameters ---

    public function testGeneratesUrlWithNoParameters(): void
    {
        $url = $this->builder->generate('/login');

        $this->assertSame('https://app.example.com/login', $url);
    }

    // --- Subdomain ---

    public function testPrependsSubdomainToDomain(): void
    {
        $url = $this->builder->generate('/dashboard', [], 'hub');

        $this->assertSame('https://hub.app.example.com/dashboard', $url);
    }

    public function testPrependsDynamicTenantSubdomain(): void
    {
        $url = $this->builder->generate('/account/activation/#[token]#', ['token' => 'abc123'], 'acme');

        $this->assertSame('https://acme.app.example.com/account/activation/abc123', $url);
    }

    public function testNullSubdomainBuildsBareApexUrl(): void
    {
        $url = $this->builder->generate('/login', [], null);

        $this->assertSame('https://app.example.com/login', $url);
    }

    // --- Port ---

    public function testAppendsPortWhenSet(): void
    {
        $builder = new BuilderUrlClient(
            protocol: 'http',
            domain: 'ctr.lvh.me',
            port: '8080',
            replaceValuesHelper: new ReplaceValuesTextFromArrayHelper(),
        );

        $url = $builder->generate('/login', [], 'acme');

        $this->assertSame('http://acme.ctr.lvh.me:8080/login', $url);
    }

    public function testOmitsPortWhenEmpty(): void
    {
        $builder = new BuilderUrlClient(
            protocol: 'https',
            domain: 'ctr.example.com',
            port: '',
            replaceValuesHelper: new ReplaceValuesTextFromArrayHelper(),
        );

        $url = $builder->generate('/login', [], 'acme');

        $this->assertSame('https://acme.ctr.example.com/login', $url);
    }
}
