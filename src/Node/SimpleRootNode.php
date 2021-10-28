<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Node;

/**
 * Root node that represents simple row without any relationship.
 */
final class SimpleRootNode extends AbstractNode implements RootNode
{
    public function parseRows(array $rows): array
    {
        return array_map(fn(array $row) => $this->parseRow($row), $rows);
    }

    public function parseRow(array $row): array
    {
        return $this->parseDataFromRow($row, $this->columns, $this->types);
    }
}
