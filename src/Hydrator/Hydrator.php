<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Hydrator;

interface Hydrator
{
    /**
     * Instantiates an object of the given type from the raw data.
     *
     * @template T of object
     * @param class-string<T> $targetClass
     * @return T
     */
    public function hydrate(mixed $data, string $targetClass): object;
}
