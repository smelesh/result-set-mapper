<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Fixtures;

use Smelesh\ResultSetMapper\Hydrator\Hydratable;

final class HydrateToWrongTargetDto implements Hydratable
{
    public static function hydrate(array $data): UserDto
    {
        return new UserDto(1, 'incorrect', new \DateTimeImmutable());
    }
}
