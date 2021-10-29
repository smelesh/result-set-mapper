<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests;

use Smelesh\ResultSetMapper\ResultSet;
use PHPUnit\Framework\TestCase;

class ResultSetTest extends TestCase
{
    public function testFetch(): void
    {
        $result = new ResultSet([
            ['id' => 1, 'name' => 'user #1'],
            ['id' => 2, 'name' => 'user #2'],
        ]);

        $this->assertSame(['id' => 1, 'name' => 'user #1'], $result->fetch());
        $this->assertSame(['id' => 2, 'name' => 'user #2'], $result->fetch());
        $this->assertNull($result->fetch());
    }

    public function testFetchWithEmptyResultSet(): void
    {
        $result = new ResultSet();

        $this->assertNull($result->fetch());
    }

    public function testFetchFromGenerator(): void
    {
        $generator = static function (): iterable {
            yield ['id' => 1, 'name' => 'user #1'];
            yield ['id' => 2, 'name' => 'user #2'];
        };

        $result = new ResultSet($generator());

        $this->assertSame(['id' => 1, 'name' => 'user #1'], $result->fetch());
        $this->assertSame(['id' => 2, 'name' => 'user #2'], $result->fetch());
        $this->assertNull($result->fetch());
    }

    public function testFetchAll(): void
    {
        $result = new ResultSet([
            ['id' => 1, 'name' => 'user #1'],
            ['id' => 2, 'name' => 'user #2'],
        ]);

        $this->assertSame([
            ['id' => 1, 'name' => 'user #1'],
            ['id' => 2, 'name' => 'user #2'],
        ], $result->fetchAll());
    }

    public function testFetchAllWithEmptyResultSet(): void
    {
        $result = new ResultSet();

        $this->assertEmpty($result->fetchAll());
    }

    public function testIterate(): void
    {
        $result = new ResultSet([
            ['id' => 1, 'name' => 'user #1'],
            ['id' => 2, 'name' => 'user #2'],
        ]);

        $this->assertSame([
            ['id' => 1, 'name' => 'user #1'],
            ['id' => 2, 'name' => 'user #2'],
        ], iterator_to_array($result->iterate()));
    }

    public function testWithProcessor(): void
    {
        $result = new ResultSet([
            ['id' => 1, 'name' => 'user #1'],
            ['id' => 2, 'name' => 'user #2'],
        ]);

        $result = $result->withProcessor(static function (\Traversable $rows): \Traversable {
            foreach ($rows as $row) {
                yield array_merge($row, ['modified' => true]);
            }
        });

        $this->assertSame(['id' => 1, 'name' => 'user #1', 'modified' => true], $result->fetch());
        $this->assertSame(['id' => 2, 'name' => 'user #2', 'modified' => true], $result->fetch());
        $this->assertNull($result->fetch());
    }
}
