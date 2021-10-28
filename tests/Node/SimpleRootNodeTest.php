<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Node;

use Smelesh\ResultSetMapper\Node\SimpleRootNode;
use PHPUnit\Framework\TestCase;
use Smelesh\ResultSetMapper\Type\SimpleTypeConverter;

class SimpleRootNodeTest extends TestCase
{
    public function testCreateNodeWithoutColumns(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Column names should be provided');

        new SimpleRootNode([]);
    }

    public function testParseRowWithSpecifiedColumns(): void
    {
        $node = new SimpleRootNode(['id', 'name']);

        $result = $node->parseRow(['id' => 1, 'name' => 'user #1', 'country' => 'BY']);

        $this->assertSame([
            'id' => 1,
            'name' => 'user #1',
        ], $result);
    }

    public function testParseRowWithColumnAlias(): void
    {
        $node = new SimpleRootNode(['id', 'user_name' => 'name']);

        $result = $node->parseRow(['id' => 1, 'name' => 'user #1']);

        $this->assertSame([
            'id' => 1,
            'user_name' => 'user #1',
        ], $result);
    }

    public function testParseRowWithUnknownColumn(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Column "unknown" does not exist in result set');

        $node = new SimpleRootNode(['id', 'unknown']);

        $node->parseRow(['id' => 1, 'name' => 'user #1']);
    }

    public function testParseNullRow(): void
    {
        $node = new SimpleRootNode(['id', 'name']);

        $result = $node->parseRow(['id' => null, 'name' => null]);

        $this->assertSame([
            'id' => null,
            'name' => null,
        ], $result);
    }

    public function testParseRowsWithSpecifiedColumns(): void
    {
        $node = new SimpleRootNode(['id', 'name']);

        $result = $node->parseRows([
            ['id' => 1, 'name' => 'user #1', 'country' => 'BY'],
            ['id' => 2, 'name' => 'user #2', 'country' => 'US'],
        ]);

        $this->assertSame([
            [
                'id' => 1,
                'name' => 'user #1',
            ],
            [
                'id' => 2,
                'name' => 'user #2',
            ],
        ], $result);
    }

    public function testParseRowsWithDuplicates(): void
    {
        $node = new SimpleRootNode(['id', 'name']);

        $result = $node->parseRows([
            ['id' => 1, 'name' => 'user #1'],
            ['id' => 1, 'name' => 'user #1'],
        ]);

        $this->assertSame([
            [
                'id' => 1,
                'name' => 'user #1',
            ],
            [
                'id' => 1,
                'name' => 'user #1',
            ],
        ], $result);
    }

    public function testParseEmptyRowsList(): void
    {
        $node = new SimpleRootNode(['id', 'name']);

        $result = $node->parseRows([]);

        $this->assertEmpty($result);
    }

    public function testParseRowsWithTypes(): void
    {
        $node = (new SimpleRootNode(['id', 'name', 'is_active']))
            ->types([
                'id' => 'int',
                'is_active' => 'bool',
            ], new SimpleTypeConverter());

        $result = $node->parseRows([
            ['id' => '1', 'name' => 'user #1', 'is_active' => '1'],
            ['id' => '2', 'name' => 'user #2', 'is_active' => '0'],
            ['id' => '3', 'name' => 'user #3', 'is_active' => null],
        ]);

        $this->assertSame([
            ['id' => 1, 'name' => 'user #1', 'is_active' => true],
            ['id' => 2, 'name' => 'user #2', 'is_active' => false],
            ['id' => 3, 'name' => 'user #3', 'is_active' => null],
        ], $result);
    }
}
