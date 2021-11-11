<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Fixtures;

use Smelesh\ResultSetMapper\Hydrator\Hydratable;

final class UserDto implements Hydratable
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }

    public static function hydrate(array $data): self
    {
        /** @var array{id: int, name: string} $data */

        return new self($data['id'], $data['name']);
    }
}
