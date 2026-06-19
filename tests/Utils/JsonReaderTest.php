<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Tests\Utils;

use Letkode\CommonBundle\Utils\JsonReader;
use PHPUnit\Framework\TestCase;

final class JsonReaderTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/letkode_json_reader_test_' . uniqid();
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->tmpDir);
    }

    public function testReadsValidJsonFile(): void
    {
        file_put_contents($this->tmpDir . '/data.json', '{"key":"value","num":42}');
        $reader = new JsonReader($this->tmpDir);

        $result = $reader->read('data.json');

        self::assertSame(['key' => 'value', 'num' => 42], $result);
    }

    public function testReturnsNullForNonExistentFile(): void
    {
        $reader = new JsonReader($this->tmpDir);

        $result = $reader->read('missing.json');

        self::assertNull($result);
    }

    public function testReadsFromSubfolder(): void
    {
        $sub = $this->tmpDir . '/sub';
        mkdir($sub);
        file_put_contents($sub . '/config.json', '["a","b"]');
        $reader = new JsonReader($this->tmpDir);

        $result = $reader->read('config.json', 'sub');

        self::assertSame(['a', 'b'], $result);
    }

    public function testThrowsOnInvalidJson(): void
    {
        file_put_contents($this->tmpDir . '/bad.json', '{invalid json}');
        $reader = new JsonReader($this->tmpDir);

        $this->expectException(\JsonException::class);
        $reader->read('bad.json');
    }

    public function testReadsEmptyObject(): void
    {
        file_put_contents($this->tmpDir . '/empty.json', '{}');
        $reader = new JsonReader($this->tmpDir);

        self::assertSame([], $reader->read('empty.json'));
    }

    public function testReturnsNullForNonExistentFileInSubfolder(): void
    {
        $reader = new JsonReader($this->tmpDir);

        self::assertNull($reader->read('nope.json', 'subfolder'));
    }
}
