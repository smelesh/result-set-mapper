<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests;

use Smelesh\ResultSetMapper\ResultSet;
use PHPUnit\Framework\TestCase;
use Smelesh\ResultSetMapper\Selector\PrefixSelector;
use Smelesh\ResultSetMapper\Selector\Selector;
use Smelesh\ResultSetMapper\Tests\Stubs\VerboseTypeConverter;

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

    public function testParseJsonColumns(): void
    {
        $result = new ResultSet([
            ['id' => 1, 'name' => 'user #1', 'subscription' => '{"id": 100, "type": "PREMIUM"}'],
        ]);

        $result = $result->parseJsonColumns(['subscription']);

        $this->assertSame([
            'id' => 1,
            'name' => 'user #1',
            'subscription' => ['id' => 100, 'type' => 'PREMIUM'],
        ], $result->fetch());
    }

    public function testMerge(): void
    {
        $result = new ResultSet([
            ['id' => 1, 'name' => 'user #1', 'subscriptions' => [['id' => 100, 'type' => 'PREMIUM']]],
            ['id' => 1, 'name' => 'user #1', 'subscriptions' => [['id' => 101, 'type' => 'LITE']]],
            ['id' => 2, 'name' => 'user #2', 'subscriptions' => [['id' => 100, 'type' => 'PREMIUM']]],
        ]);

        $result = $result->mergeRoot(['id'])
            ->merge('subscriptions', ['id']);

        $this->assertSame([
            ['id' => 1, 'name' => 'user #1', 'subscriptions' => [
                ['id' => 100, 'type' => 'PREMIUM'],
                ['id' => 101, 'type' => 'LITE'],
            ]],
            ['id' => 2, 'name' => 'user #2', 'subscriptions' => [
                ['id' => 100, 'type' => 'PREMIUM'],
            ]],
        ], $result->fetchAll());
    }

    /**
     * @dataProvider provideEmbeddableSelector
     */
    public function testEmbed(string|array|Selector $selector): void
    {
        $result = new ResultSet([
            ['id' => 1, 'name' => 'user #1', 's_id' => 100, 's_type' => 'PREMIUM'],
        ]);

        $result = $result->embed('subscription', $selector, ['s_id']);

        $this->assertSame([
            'id' => 1,
            'name' => 'user #1',
            's_id' => 100,
            'subscription' => ['id' => 100, 'type' => 'PREMIUM'],
        ], $result->fetch());
    }

    public function provideEmbeddableSelector(): iterable
    {
        yield 'prefix' => ['s_'];
        yield 'names' => [['id' => 's_id', 'type' => 's_type']];
        yield 'custom' => [new PrefixSelector('s_')];
    }

    public function testTypes(): void
    {
        $result = new ResultSet([
            ['id' => '1'],
        ]);

        $result = $result->types([
            'id' => 'int',
        ]);

        $this->assertSame([
            'id' => 1,
        ], $result->fetch());
    }

    public function testTypesWithCustomInlineConverter(): void
    {
        $result = new ResultSet([
            ['id' => '1'],
        ]);

        $result = $result->types([
            'id' => 'int',
        ], new VerboseTypeConverter());

        $this->assertSame([
            'id' => 'convert(\'1\', int)',
        ], $result->fetch());
    }

    public function testTypesWithCustomDefaultConverter(): void
    {
        $result = new ResultSet([
            ['id' => '1'],
        ]);

        $result = $result->withDefaultTypeConverter(new VerboseTypeConverter())
            ->types([
                'id' => 'int',
            ]);

        $this->assertSame([
            'id' => 'convert(\'1\', int)',
        ], $result->fetch());
    }
}
