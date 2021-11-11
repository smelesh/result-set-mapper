<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Hydrator;

/**
 * Interface that class must implement to be hydrated by {@link SimpleHydrator}.
 */
interface Hydratable
{
    /**
     * Creates an instance of current class populated by the given raw data.
     *
     * @param array $data
     */
    public static function hydrate(array $data): self;
}
