<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Node;

/**
 * Base node that provides common functionality for other nodes.
 */
abstract class AbstractNode implements Node
{
    /**
     * @var non-empty-array<array-key, string>
     */
    protected array $columns;

    /**
     * @param array<array-key, string> $columns List of column names parsed by this node.
     *                                          String keys are used as aliases in a returned result.
     */
    public function __construct(array $columns)
    {
        if (empty($columns)) {
            throw new \InvalidArgumentException('Column names should be provided');
        }

        $this->columns = $columns;
    }

    /**
     * @param non-empty-array<string, mixed> $row
     * @param non-empty-array<array-key, string> $columns
     * @return non-empty-array<string, mixed>
     */
    final protected function parseDataFromRow(array $row, array $columns): array
    {
        $result = [];

        foreach ($columns as $alias => $name) {
            $alias = is_string($alias) ? $alias : $name;

            $result[$alias] = array_key_exists($name, $row)
                ? $row[$name]
                : throw new \LogicException(sprintf('Column "%s" does not exist in result set', $name));
        }

        return $result;
    }
}
