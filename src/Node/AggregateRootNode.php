<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Node;

/**
 * Root node representation in result set with relations.
 * Removes duplicated records produced by JOINed tables.
 */
final class AggregateRootNode extends RelationalNode implements RootNode
{
    public function parseRows(array $rows): array
    {
        $index = new Index();

        foreach ($rows as $row) {
            $this->parseRelationalRow($row, $index);
        }

        return $index->findAll();
    }

    public function parseRow(array $row): array
    {
        $index = new Index();

        $this->parseRelationalRow($row, $index);

        return $index->findFirst()
            ?? throw new \RuntimeException('Unable to parse a row, got empty result');
    }
}
