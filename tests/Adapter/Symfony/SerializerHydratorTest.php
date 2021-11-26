<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Adapter\Symfony;

use Smelesh\ResultSetMapper\Adapter\Symfony\SerializerHydrator;
use PHPUnit\Framework\TestCase;
use Smelesh\ResultSetMapper\Tests\Fixtures\UserDto;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class SerializerHydratorTest extends TestCase
{
    public function testHydrate(): void
    {
        $serializer = $this->createSerializer();
        $hydrator = new SerializerHydrator($serializer);

        $result = $hydrator->hydrate([
            'id' => '1',
            'name' => 'user #1',
        ], UserDto::class);

        $this->assertEquals(new UserDto(1, 'user #1'), $result);
    }

    public function testHydrateWithIncompatibleInputData(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to hydrate to "Smelesh\\ResultSetMapper\\Tests\\Fixtures\\UserDto"');

        $serializer = $this->createSerializer();
        $hydrator = new SerializerHydrator($serializer);

        $hydrator->hydrate([
            'id' => new \DateTimeImmutable(),
            'name' => 'user #1',
        ], UserDto::class);
    }

    private function createSerializer(): Serializer
    {
        $normalizer = new PropertyNormalizer();

        return new Serializer([$normalizer]);
    }
}
