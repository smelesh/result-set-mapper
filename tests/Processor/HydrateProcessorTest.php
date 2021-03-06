<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Processor;

use Smelesh\ResultSetMapper\Hydrator\SimpleHydrator;
use Smelesh\ResultSetMapper\Processor\HydrateProcessor;
use PHPUnit\Framework\TestCase;
use Smelesh\ResultSetMapper\ResultSet;
use Smelesh\ResultSetMapper\Tests\Fixtures\UserDto;

class HydrateProcessorTest extends TestCase
{
    public function testProcessor(): void
    {
        $processor = new HydrateProcessor(new SimpleHydrator(), UserDto::class);

        $result = ResultSet::fromRows([
            ['id' => 1, 'name' => 'user #1', 'created_at' => '2021-11-26 01:02:03'],
            ['id' => 2, 'name' => 'user #2', 'created_at' => '2021-11-27 01:02:03'],
        ])->withProcessor($processor);

        $this->assertEquals([
            new UserDto(1, 'user #1', new \DateTimeImmutable('2021-11-26 01:02:03')),
            new UserDto(2, 'user #2', new \DateTimeImmutable('2021-11-27 01:02:03')),
        ], $result->fetchAll());
    }
}
