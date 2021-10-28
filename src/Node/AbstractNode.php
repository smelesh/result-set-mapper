<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Node;

use Smelesh\ResultSetMapper\Type\TypeConverter;

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
     * @var array<string, string>
     */
    protected array $types = [];

    private ?TypeConverter $typeConverter = null;

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

    public function types(array $types, TypeConverter $typeConverter): self
    {
        $this->types = $types;
        $this->typeConverter = $typeConverter;

        return $this;
    }

    /**
     * @param non-empty-array<string, mixed> $row
     * @param non-empty-array<array-key, string> $columns
     * @param array<string, string> $types
     * @return non-empty-array<string, mixed>
     */
    final protected function parseDataFromRow(array $row, array $columns, array $types = []): array
    {
        $result = [];

        foreach ($columns as $alias => $name) {
            $value = array_key_exists($name, $row)
                ? $row[$name]
                : throw new \LogicException(sprintf('Column "%s" does not exist in result set', $name));

            if (isset($types[$name])) {
                if ($this->typeConverter === null) {
                    throw new \LogicException('Type converter is required to convert column type');
                }

                $value = $this->typeConverter->convert($value, $types[$name]);
            }

            $alias = is_string($alias) ? $alias : $name;

            $result[$alias] = $value;
        }

        return $result;
    }
}
