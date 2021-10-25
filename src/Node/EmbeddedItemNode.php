<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Node;

/**
 * Single item representation in one-to-one, one-to-many relationships.
 */
final class EmbeddedItemNode extends RelationalNode
{
    protected function getResultFromIndex(Index $index): ?array
    {
        return $index->findFirst();
    }
}
