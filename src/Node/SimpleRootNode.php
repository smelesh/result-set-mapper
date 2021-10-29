<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Node;

/**
 * Root node that represents simple row without any relationship.
 */
final class SimpleRootNode extends AbstractNode implements RootNode
{
    public function parseRows(\Traversable $rows): \Traversable
    {
        foreach ($rows as $row) {
            yield $this->parseDataFromRow($row, $this->columns, $this->types);
        }
    }
}
