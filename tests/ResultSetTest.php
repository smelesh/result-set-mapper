<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests;

use Smelesh\ResultSetMapper\ResultSet;
use PHPUnit\Framework\TestCase;
use Smelesh\ResultSetMapper\Selector\PrefixSelector;
use Smelesh\ResultSetMapper\Selector\Selector;
use Smelesh\ResultSetMapper\Tests\Fixtures\UserDto;
use Smelesh\ResultSetMapper\Tests\Stubs\NamedArgumentsHydrator;
use Smelesh\ResultSetMapper\Tests\Stubs\VerboseTypeConverter;
use Smelesh\ResultSetMapper\Type\SimpleTypeConverter;

class ResultSetTest extends TestCase
{
    public function testCreateFromArray(): void
    {
        $result = ResultSet::fromRows([
            ['id' => 1, 'name' => 'user #1'],
            ['id' => 2, 'name' => 'user #2'],
        ]);

        $this->assertSame([
            ['id' => 1, 'name' => 'user #1'],
            ['id' => 2, 'name' => 'user #2'],
        ], $result->fetchAll());
    }

    public function testCreateFromGenerator(): void
    {
        $generator = static function (): iterable {
            yield ['id' => 1, 'name' => 'user #1'];
            yield ['id' => 2, 'name' => 'user #2'];
        };

        $result = ResultSet::fromRows($generator());

        $this->assertSame([
            ['id' => 1, 'name' => 'user #1'],
            ['id' => 2, 'name' => 'user #2'],
        ], $result->fetchAll());
    }

    public function testFetch(): void
    {
        $result = ResultSet::fromRows([
            ['id' => 1, 'name' => 'user #1'],
            ['id' => 2, 'name' => 'user #2'],
        ]);

        $this->assertSame(['id' => 1, 'name' => 'user #1'], $result->fetch());
        $this->assertSame(['id' => 2, 'name' => 'user #2'], $result->fetch());
        $this->assertNull($result->fetch());
    }

    public function testFetchWithEmptyResultSet(): void
    {
        $result = ResultSet::fromRows([]);

        $this->assertNull($result->fetch());
    }

    public function testFetchFromGenerator(): void
    {
        $generator = static function (): iterable {
            yield ['id' => 1, 'name' => 'user #1'];
            yield ['id' => 2, 'name' => 'user #2'];
        };

        $result = ResultSet::fromRows($generator());

        $this->assertSame(['id' => 1, 'name' => 'user #1'], $result->fetch());
        $this->assertSame(['id' => 2, 'name' => 'user #2'], $result->fetch());
        $this->assertNull($result->fetch());
    }

    public function testFetchAll(): void
    {
        $result = ResultSet::fromRows([
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
        $result = ResultSet::fromRows([]);

        $this->assertEmpty($result->fetchAll());
    }

    public function testIterate(): void
    {
        $result = ResultSet::fromRows([
            ['id' => 1, 'name' => 'user #1'],
            ['id' => 2, 'name' => 'user #2'],
        ]);

        $this->assertSame([
            ['id' => 1, 'name' => 'user #1'],
            ['id' => 2, 'name' => 'user #2'],
        ], iterator_to_array($result->iterate()));
    }

    public function testDeferReadUntilFetch(): void
    {
        $totalReads = 0;

        $generator = static function () use (&$totalReads): \Traversable {
            for ($i = 1; $i <= 2; $i++) {
                $totalReads++;
                yield ['id' => $i];
            }
        };

        $result = ResultSet::fromRows($generator());
        $this->assertSame(0, $totalReads);

        $this->assertNotNull($result->fetch());
        $this->assertSame(1, $totalReads);

        $this->assertNotNull($result->fetch());
        $this->assertSame(2, $totalReads);

        $this->assertNull($result->fetch());
        $this->assertSame(2, $totalReads);
    }

    public function testFetchAllShouldFailWhenAlreadyRead(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot rewind a generator that was already run');

        $result = ResultSet::fromRows([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ]);

        $this->assertNotNull($result->fetch());
        $this->assertNotNull($result->fetch());

        $this->assertNull($result->fetchAll());
    }

    public function testIterateShouldFailWhenAlreadyRead(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot rewind a generator that was already run');

        $result = ResultSet::fromRows([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ]);

        $this->assertNotNull($result->fetch());
        $this->assertNotNull($result->fetch());

        foreach ($result->iterate() as $row) {
            $this->fail('Iterating is not allowed');
        }
    }

    public function testWithProcessor(): void
    {
        $result = ResultSet::fromRows([
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

    public function testPreserveExistingContextWhenDecorateResultSet(): void
    {
        $result = ResultSet::fromRows([
            ['id' => 1, 'name' => 'user #1', 'createdAt' => new \DateTimeImmutable('2021-11-26 01:02:03')],
        ])
            ->withDefaultTypeConverter(new VerboseTypeConverter())
            ->withDefaultHydrator(new NamedArgumentsHydrator())
            ->types(['name' => 'string'])
            ->hydrate(UserDto::class);

        $this->assertEquals(new UserDto(
            1,
            'convert(\'user #1\', string)',
            new \DateTimeImmutable('2021-11-26 01:02:03'),
        ), $result->fetch());
    }

    public function testParseJsonColumns(): void
    {
        $result = ResultSet::fromRows([
            [
                'id' => 1,
                'name' => 'user #1',
                'subscription' => '{"id": 100, "type": "PREMIUM"}',
                'payments' => '[{"id": 1000, "amount": 1.99}, {"id": 1001, "amount": 0.99}]',
            ],
        ]);

        $result = $result->parseJsonColumns(['subscription', 'payments']);

        $this->assertSame([
            'id' => 1,
            'name' => 'user #1',
            'subscription' => ['id' => 100, 'type' => 'PREMIUM'],
            'payments' => [
                ['id' => 1000, 'amount' => 1.99],
                ['id' => 1001, 'amount' => 0.99],
            ],
        ], $result->fetch());
    }

    public function testMerge(): void
    {
        $result = ResultSet::fromRows([
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
        $result = ResultSet::fromRows([
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
        $result = ResultSet::fromRows([
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
        $result = ResultSet::fromRows([
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
        $result = ResultSet::fromRows([
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

    public function testHydrate(): void
    {
        $result = ResultSet::fromRows([
            ['id' => 1, 'name' => 'user #1', 'created_at' => '2021-11-26 01:02:03'],
        ]);

        $result = $result->hydrate(UserDto::class);

        $this->assertEquals(new UserDto(
            1,
            'user #1',
            new \DateTimeImmutable('2021-11-26 01:02:03'),
        ), $result->fetch());
    }

    public function testHydrateWithCustomInlineHydrator(): void
    {
        $result = ResultSet::fromRows([
            ['id' => 1, 'name' => 'user #1', 'createdAt' => new \DateTimeImmutable('2021-11-26 01:02:03')],
        ]);

        $result = $result->hydrate(UserDto::class, new NamedArgumentsHydrator());

        $this->assertEquals(new UserDto(
            1,
            'user #1',
            new \DateTimeImmutable('2021-11-26 01:02:03'),
        ), $result->fetch());
    }

    public function testHydrateWithCustomDefaultHydrator(): void
    {
        $result = ResultSet::fromRows([
            ['id' => 1, 'name' => 'user #1', 'createdAt' => new \DateTimeImmutable('2021-11-26 01:02:03')],
        ]);

        $result = $result
            ->withDefaultHydrator(new NamedArgumentsHydrator())
            ->hydrate(UserDto::class);

        $this->assertEquals(new UserDto(
            1,
            'user #1',
            new \DateTimeImmutable('2021-11-26 01:02:03'),
        ), $result->fetch());
    }
}
