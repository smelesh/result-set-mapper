<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Hydrator;

use Smelesh\ResultSetMapper\Hydrator\SimpleHydrator;
use PHPUnit\Framework\TestCase;
use Smelesh\ResultSetMapper\Tests\Fixtures\HydrateToWrongTargetDto;
use Smelesh\ResultSetMapper\Tests\Fixtures\NotHydratableDto;
use Smelesh\ResultSetMapper\Tests\Fixtures\UserDto;

class SimpleHydratorTest extends TestCase
{
    public function testHydrate(): void
    {
        $hydrator = new SimpleHydrator();

        $result = $hydrator->hydrate([
            'id' => 1,
            'name' => 'user #1',
            'created_at' => '2021-11-26 01:02:03',
        ], UserDto::class);

        $this->assertEquals(new UserDto(
            1,
            'user #1',
            new \DateTimeImmutable('2021-11-26 01:02:03'),
        ), $result);
    }

    public function testHydrateFailedByHydratable(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to hydrate to "Smelesh\\ResultSetMapper\\Tests\\Fixtures\\UserDto"');

        $hydrator = new SimpleHydrator();

        $hydrator->hydrate([
            'id' => null,
            'name' => 'user #1',
            'created_at' => new \DateTimeImmutable('2021-11-26 01:02:03'),
        ], UserDto::class);
    }

    public function testHydrateWithUnsupportedInputData(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to hydrate from "string", expected array');

        $hydrator = new SimpleHydrator();

        $hydrator->hydrate('input data', UserDto::class);
    }

    public function testHydrateToUnknownTarget(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Target class "Smelesh\\ResultSetMapper\\Tests\\Fixtures\\UnknownTargetDto" does not exist'
        );

        $hydrator = new SimpleHydrator();

        $hydrator->hydrate([
            'id' => 1,
            'name' => 'user #1',
            'created_at' => new \DateTimeImmutable('2021-11-26 01:02:03'),
        ], 'Smelesh\\ResultSetMapper\\Tests\\Fixtures\\UnknownTargetDto');
    }

    public function testHydrateToUnsupportedTarget(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Target class "Smelesh\\ResultSetMapper\\Tests\\Fixtures\\NotHydratableDto"'
            . ' must be a subtype of "Smelesh\\ResultSetMapper\\Hydrator\\Hydratable"'
        );

        $hydrator = new SimpleHydrator();

        $hydrator->hydrate([
            'id' => 1,
            'name' => 'user #1',
            'created_at' => new \DateTimeImmutable('2021-11-26 01:02:03'),
        ], NotHydratableDto::class);
    }

    public function testHydrateWhenHydratedToWrongTarget(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Unexpected value returned from object hydrator,'
            . ' expected "Smelesh\\ResultSetMapper\\Tests\\Fixtures\\HydrateToWrongTargetDto",'
            . ' got "Smelesh\\ResultSetMapper\\Tests\\Fixtures\\UserDto"'
        );

        $hydrator = new SimpleHydrator();

        $hydrator->hydrate([
            'id' => 1,
            'name' => 'user #1',
            'created_at' => new \DateTimeImmutable('2021-11-26 01:02:03'),
        ], HydrateToWrongTargetDto::class);
    }
}
