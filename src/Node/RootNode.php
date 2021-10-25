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
     * @param list<non-empty-array<string, mixed>> $rows
     * @return list<non-empty-array<string, mixed>>
     */
    public function parseRows(array $rows): array;

    /**
     * Parses data from given row and processes it according to node schema.
     *
     * @param non-empty-array<string, mixed> $row
     * @return non-empty-array<string, mixed>
     */
    public function parseRow(array $row): array;
}
