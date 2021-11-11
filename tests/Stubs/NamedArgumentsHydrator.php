<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Stubs;

use Smelesh\ResultSetMapper\Hydrator\Hydrator;

final class NamedArgumentsHydrator implements Hydrator
{
    public function hydrate(mixed $data, string $targetClass): object
    {
        return new $targetClass(...$data);
    }
}
