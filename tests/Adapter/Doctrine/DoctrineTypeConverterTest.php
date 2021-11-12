<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Adapter\Doctrine;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Smelesh\ResultSetMapper\Adapter\Doctrine\DoctrineTypeConverter;
use PHPUnit\Framework\TestCase;

class DoctrineTypeConverterTest extends TestCase
{
    public function testConvertSupportedType(): void
    {
        $converter = new DoctrineTypeConverter(new MySQLPlatform());

        $result = $converter->convert('10', Types::INTEGER);

        $this->assertSame(10, $result);
    }

    public function testConvertUnsupportedType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown type "unknown"');

        $converter = new DoctrineTypeConverter(new MySQLPlatform());

        $converter->convert('10', 'unknown');
    }

    public function testConvertInvalidData(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unable to convert value of type "string" to type "datetime_immutable"');

        $converter = new DoctrineTypeConverter(new MySQLPlatform());

        $converter->convert('09/Nov/2011 21:55:59', Types::DATETIME_IMMUTABLE);
    }
}
