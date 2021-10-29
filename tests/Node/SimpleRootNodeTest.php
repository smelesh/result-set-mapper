<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Node;

use Smelesh\ResultSetMapper\Node\SimpleRootNode;
use PHPUnit\Framework\TestCase;

class SimpleRootNodeTest extends TestCase
{
    public function testCreateNodeWithoutColumns(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Column names should be provided');

        new SimpleRootNode([]);
    }

    public function testParseRowsWithSpecifiedColumns(): void
    {
        $node = new SimpleRootNode(['id', 'name']);

        $result = $node->parseRows(new \ArrayIterator([
            ['id' => 1, 'name' => 'user #1', 'country' => 'BY'],
            ['id' => 2, 'name' => 'user #2', 'country' => 'US'],
        ]));

        $this->assertSame([
            [
                'id' => 1,
                'name' => 'user #1',
            ],
            [
                'id' => 2,
                'name' => 'user #2',
            ],
        ], iterator_to_array($result));
    }

    public function testParseRowsWithColumnAlias(): void
    {
        $node = new SimpleRootNode(['id', 'user_name' => 'name']);

        $result = $node->parseRows(new \ArrayIterator([
            ['id' => 1, 'name' => 'user #1'],
            ['id' => 2, 'name' => 'user #2'],
        ]));

        $this->assertSame([
            ['id' => 1, 'user_name' => 'user #1'],
            ['id' => 2, 'user_name' => 'user #2'],
        ], iterator_to_array($result));
    }

    public function testParseRowsWithUnknownColumn(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Column "unknown" does not exist in result set');

        $node = new SimpleRootNode(['id', 'unknown']);

        $result = $node->parseRows(new \ArrayIterator([
            ['id' => 1, 'name' => 'user #1'],
        ]));

        iterator_to_array($result);
    }

    public function testParseRowsWithDuplicates(): void
    {
        $node = new SimpleRootNode(['id', 'name']);

        $result = $node->parseRows(new \ArrayIterator([
            ['id' => 1, 'name' => 'user #1'],
            ['id' => 1, 'name' => 'user #1'],
        ]));

        $this->assertSame([
            [
                'id' => 1,
                'name' => 'user #1',
            ],
            [
                'id' => 1,
                'name' => 'user #1',
            ],
        ], iterator_to_array($result));
    }

    public function testParseEmptyRowsList(): void
    {
        $node = new SimpleRootNode(['id', 'name']);

        $result = $node->parseRows(new \ArrayIterator());

        $this->assertEmpty(iterator_to_array($result));
    }

    public function testParseNullRow(): void
    {
        $node = new SimpleRootNode(['id', 'name']);

        $result = $node->parseRows(new \ArrayIterator([
            ['id' => null, 'name' => null],
        ]));

        $this->assertSame([
            ['id' => null, 'name' => null],
        ], iterator_to_array($result));
    }
}
