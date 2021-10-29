<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Node;

/**
 * Root node that is responsible for parsing input data.
 */
interface RootNode extends Node
{
    /**
     * Parses data from given rows and processes them according to node schema.
     *
     * @param \Traversable<non-empty-array<string, mixed>> $rows
     * @return \Traversable<non-empty-array<string, mixed>>
     */
    public function parseRows(\Traversable $rows): \Traversable;
}
