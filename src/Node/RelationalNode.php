<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Node;

/**
 * Base node that provides common functionality for relation parsing.
 */
abstract class RelationalNode extends AbstractNode
{
    /**
     * @var non-empty-list<string>
     */
    private array $primaryKey;

    /**
     * @var array<string, EmbeddedNode>
     */
    private array $relations = [];

    /**
     * @param array<array-key, string> $columns List of column names parsed by this node.
     *                                          String keys are used as aliases in a returned result.
     * @param list<string> $primaryKey List of primary key column names
     */
    public function __construct(array $columns, array $primaryKey)
    {
        parent::__construct($columns);

        if (empty($primaryKey)) {
            throw new \InvalidArgumentException('Primary key should be provided');
        }

        $this->primaryKey = $primaryKey;
    }

    /**
     * Adds relation to current node.
     */
    final public function join(string $name, EmbeddedNode $node): self
    {
        $this->relations[$name] = $node;

        return $this;
    }

    /**
     * @param non-empty-array<string, mixed> $row
     */
    final protected function parseRelationalRow(array $row, Index $index): void
    {
        $primaryKey = $this->parseDataFromRow($row, $this->primaryKey);

        if (in_array(null, $primaryKey, true)) {
            // empty relation, nothing to parse
            return;
        }

        $item = $index->find($primaryKey) ?? $this->parseDataFromRow($row, $this->columns);

        foreach ($this->relations as $relationName => $relationNode) {
            $relationIndex = $index->nestedIndex($relationName, $primaryKey);

            $relationNode->parseRelationalRow($row, $relationIndex);

            $item[$relationName] = $relationNode->getResultFromIndex($relationIndex);
        }

        $index->set($primaryKey, $item);
    }

    /**
     * Returns result records from index.
     *
     * Collection-like nodes returns list of records or an empty list.
     * Singular nodes returns a single record or `null`.
     *
     * @return list<non-empty-array<string, mixed>>|non-empty-array<string, mixed>|null
     */
    protected function getResultFromIndex(Index $index): ?array
    {
        return $index->findAll();
    }
}
