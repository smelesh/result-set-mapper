<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Type;

use Smelesh\ResultSetMapper\Type\SimpleTypeConverter;
use PHPUnit\Framework\TestCase;

class SimpleTypeConverterTest extends TestCase
{
    /**
     * @dataProvider provideSupportedSimpleTypes
     */
    public function testConvertSupportedSimpleType(string $type, mixed $value, mixed $expectedResult): void
    {
        $converter = new SimpleTypeConverter();

        $result = $converter->convert($value, $type);

        $this->assertSame($expectedResult, $result);
    }

    public function provideSupportedSimpleTypes(): iterable
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

    /**
     * @dataProvider provideValidDateTime
     */
    public function testConvertValidDateTime(mixed $value, ?\DateTimeImmutable $expectedResult): void
    {
        $converter = new SimpleTypeConverter();

        $result = $converter->convert($value, 'datetime');

        $this->assertEquals($expectedResult, $result);
    }

    public function provideValidDateTime(): iterable
    {
        yield [null, null];
        yield ['2021-11-09 21:55:59', new \DateTimeImmutable('2021-11-09 21:55:59.000000 +00:00')];
        yield ['2021-11-09 21:55:59.123456', new \DateTimeImmutable('2021-11-09 21:55:59.123456 +00:00')];
        yield ['2021-11-09 21:55:59.123', new \DateTimeImmutable('2021-11-09 21:55:59.123000 +00:00')];
        yield ['2021-11-09 21:55:59+03', new \DateTimeImmutable('2021-11-09 21:55:59.000000 +03:00')];
        yield ['2021-11-09 21:55:59.123+03', new \DateTimeImmutable('2021-11-09 21:55:59.123000 +03:00')];
    }

    public function testConvertInvalidDateTime(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unable to convert value of type "string" to type "datetime"');

        $converter = new SimpleTypeConverter();

        $converter->convert('09/Nov/2011 21:55:59', 'datetime');
    }

    /**
     * @dataProvider provideValidJsonString
     */
    public function testConvertValidJson(mixed $value, mixed $expectedResult): void
    {
        $converter = new SimpleTypeConverter();

        $result = $converter->convert($value, 'json');

        $this->assertSame($expectedResult, $result);
    }

    public function provideValidJsonString(): iterable
    {
        yield [null, null];
        yield ['{"id": 10, "type": "PREMIUM"}', ['id' => 10, 'type' => 'PREMIUM']];
        yield ['[10, 20, 30]', [10, 20, 30]];
        yield ['null', null];
        yield ['"string"', 'string'];
        yield ['{}', []];
        yield ['[]', []];
    }

    public function testConvertInvalidJson(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unable to convert value of type "string" to type "json"');

        $converter = new SimpleTypeConverter();

        $converter->convert('invalid_json', 'json');
    }
}
