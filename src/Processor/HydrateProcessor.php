<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Processor;

use Smelesh\ResultSetMapper\Hydrator\Hydrator;

/**
 * Converts each row into object of the given class.
 */
final class HydrateProcessor
{
    /**
     * @param class-string $targetClass
     */
    public function __construct(private Hydrator $hydrator, private string $targetClass)
    {
    }

    public function __invoke(\Traversable $rows): \Traversable
    {
        foreach ($rows as $row) {
            yield $this->hydrator->hydrate($row, $this->targetClass);
        }
    }
}
