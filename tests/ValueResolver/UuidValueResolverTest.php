<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Tests\ValueResolver;

use Letkode\CommonBundle\Attribute\Mapping\MapUuid;
use Letkode\CommonBundle\ValueResolver\UuidValueResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UuidValueResolverTest extends TestCase
{
    private TranslatorInterface $translator;
    private UuidValueResolver $resolver;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->resolver = new UuidValueResolver($this->translator);
    }

    public function testResolvesValidUuid(): void
    {
        $uuid = Uuid::v7();
        $request = new Request();
        $request->attributes->set('uuid', (string) $uuid);

        $argument = new ArgumentMetadata('uuid', Uuid::class, false, false, null, false, [new MapUuid()]);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        self::assertCount(1, $result);
        self::assertInstanceOf(Uuid::class, $result[0]);
        self::assertSame((string) $uuid, (string) $result[0]);
    }

    public function testReturnsEmptyWhenNoMapUuidAttribute(): void
    {
        $request = new Request();
        $request->attributes->set('uuid', (string) Uuid::v7());

        $argument = new ArgumentMetadata('uuid', Uuid::class, false, false, null, false, []);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        self::assertSame([], $result);
    }

    public function testReturnsEmptyWhenTypeIsNotUuid(): void
    {
        $request = new Request();
        $request->attributes->set('id', '123');

        $argument = new ArgumentMetadata('id', 'string', false, false, null, false, [new MapUuid()]);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        self::assertSame([], $result);
    }

    public function testThrowsOnInvalidUuidString(): void
    {
        $this->translator->method('trans')->willReturn('Invalid UUID.');
        $request = new Request();
        $request->attributes->set('uuid', 'not-a-uuid');

        $argument = new ArgumentMetadata('uuid', Uuid::class, false, false, null, false, [new MapUuid()]);

        $this->expectException(BadRequestHttpException::class);
        iterator_to_array($this->resolver->resolve($request, $argument));
    }

    public function testThrowsWhenAttributeValueIsNotString(): void
    {
        $this->translator->method('trans')->willReturn('Invalid UUID.');
        $request = new Request();
        $request->attributes->set('uuid', 12345);

        $argument = new ArgumentMetadata('uuid', Uuid::class, false, false, null, false, [new MapUuid()]);

        $this->expectException(BadRequestHttpException::class);
        iterator_to_array($this->resolver->resolve($request, $argument));
    }

    public function testUsesCustomTranslationKey(): void
    {
        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('my.custom.key', self::anything(), 'my_domain')
            ->willReturn('Custom error.');

        $request = new Request();
        $request->attributes->set('uuid', 'invalid');

        $attribute = new ArgumentMetadata('uuid', Uuid::class, false, false, null, false, [
            new MapUuid(invalidKey: 'my.custom.key', domain: 'my_domain'),
        ]);

        $this->expectException(BadRequestHttpException::class);
        iterator_to_array($this->resolver->resolve($request, $attribute));
    }
}
