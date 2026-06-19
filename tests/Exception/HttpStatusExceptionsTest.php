<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Tests\Exception;

use Letkode\CommonBundle\Exception\BadRequestException;
use Letkode\CommonBundle\Exception\EntityNotFoundException;
use Letkode\CommonBundle\Exception\HttpStatusExceptionInterface;
use Letkode\CommonBundle\Exception\TooManyRequestsException;
use Letkode\CommonBundle\Exception\UnauthorizedException;
use Letkode\CommonBundle\Exception\ValueObjectException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class HttpStatusExceptionsTest extends TestCase
{
    /**
     * @return array<string, array{class-string<HttpStatusExceptionInterface&\RuntimeException>, int}>
     */
    public static function exceptionStatusProvider(): array
    {
        return [
            'BadRequestException returns 400' => [BadRequestException::class, Response::HTTP_BAD_REQUEST],
            'UnauthorizedException returns 401' => [UnauthorizedException::class, Response::HTTP_UNAUTHORIZED],
            'EntityNotFoundException returns 404' => [EntityNotFoundException::class, Response::HTTP_NOT_FOUND],
            'TooManyRequestsException returns 429' => [TooManyRequestsException::class, Response::HTTP_TOO_MANY_REQUESTS],
        ];
    }

    /**
     * @param class-string<HttpStatusExceptionInterface&\RuntimeException> $class
     */
    #[DataProvider('exceptionStatusProvider')]
    public function testGetStatusCode(string $class, int $expectedStatus): void
    {
        $exception = new $class('error');

        self::assertSame($expectedStatus, $exception->getStatusCode());
        self::assertInstanceOf(HttpStatusExceptionInterface::class, $exception);
        self::assertInstanceOf(\RuntimeException::class, $exception);
    }

    #[DataProvider('exceptionStatusProvider')]
    public function testExceptionCarriesMessage(string $class, int $expectedStatus): void
    {
        $exception = new $class('Something went wrong.');

        self::assertSame('Something went wrong.', $exception->getMessage());
    }

    #[DataProvider('exceptionStatusProvider')]
    public function testExceptionIsThrowable(string $class, int $expectedStatus): void
    {
        $this->expectException($class);
        throw new $class('thrown');
    }

    public function testValueObjectExceptionCarriesTranslationKey(): void
    {
        $e = new ValueObjectException('Invalid email.', translationKey: 'value_object.email.invalid', translationParams: ['{{ value }}' => 'bad@']);

        self::assertSame('Invalid email.', $e->getMessage());
        self::assertSame('value_object.email.invalid', $e->translationKey);
        self::assertSame(['{{ value }}' => 'bad@'], $e->translationParams);
    }

    public function testValueObjectExceptionWithoutParams(): void
    {
        $e = new ValueObjectException('Error.', translationKey: 'some.key');

        self::assertSame([], $e->translationParams);
    }

    public function testValueObjectExceptionIsThrowable(): void
    {
        $this->expectException(ValueObjectException::class);
        throw new ValueObjectException('fail', translationKey: 'key');
    }
}
