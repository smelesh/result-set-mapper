<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Type;

use Smelesh\ResultSetMapper\Type\SimpleTypeConverter;
use PHPUnit\Framework\TestCase;

class SimpleTypeConverterTest extends TestCase
{
    /**
     * @dataProvider provideSupportedTypes
     */
    public function testConvertSupportedType(string $type, mixed $value, mixed $expectedResult): void
    {
        $converter = new SimpleTypeConverter();

        $result = $converter->convert($value, $type);

        $this->assertSame($expectedResult, $result);
    }

    public function provideSupportedTypes(): iterable
    {
        yield ["int", "10.99", 10];
        yield ["int", 10.99, 10];
        yield ["int", 10, 10];

        yield ["float", "10.99", 10.99];
        yield ["float", 10.99, 10.99];

        yield ["bool", "1", true];
        yield ["bool", "0", false];
        yield ["bool", true, true];
        yield ["bool", false, false];

        yield ["string", "test string", "test string"];

        yield ["int", null, null];
        yield ["float", null, null];
        yield ["bool", null, null];
        yield ["string", null, null];
    }

    public function testConvertUnsupportedType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown type "unknown"');

        $converter = new SimpleTypeConverter();

        $converter->convert('test', 'unknown');
    }
}
