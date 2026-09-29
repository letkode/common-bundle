<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Tests\Exception;

use Letkode\CommonBundle\Exception\Validation\ValueObjectException;
use PHPUnit\Framework\TestCase;

final class ValueObjectExceptionTest extends TestCase
{
    public function testCarriesTranslationKeyAndParams(): void
    {
        $e = new ValueObjectException('Invalid email.', translationKey: 'value_object.email.invalid', translationParams: ['{{ value }}' => 'bad@']);

        self::assertSame('Invalid email.', $e->getMessage());
        self::assertSame('value_object.email.invalid', $e->translationKey);
        self::assertSame(['{{ value }}' => 'bad@'], $e->translationParams);
    }

    public function testWithoutParams(): void
    {
        $e = new ValueObjectException('Error.', translationKey: 'some.key');

        self::assertSame([], $e->translationParams);
    }

    public function testIsThrowable(): void
    {
        $this->expectException(ValueObjectException::class);
        throw new ValueObjectException('fail', translationKey: 'key');
    }

    public function testIsNotFinalSoItCanBeExtended(): void
    {
        self::assertFalse(new \ReflectionClass(ValueObjectException::class)->isFinal());
    }
}
