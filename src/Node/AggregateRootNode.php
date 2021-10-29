<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Node;

/**
 * Root node representation in result set with relations.
 * Removes duplicated records produced by JOINed tables.
 */
final class AggregateRootNode extends RelationalNode implements RootNode
{
    public function parseRows(\Traversable $rows): \Traversable
    {
        $index = new Index();

        foreach ($rows as $row) {
            $this->parseRelationalRow($row, $index);
        }

        $result = $index->findAll();

        unset($index);

        yield from $result;
    }
}
