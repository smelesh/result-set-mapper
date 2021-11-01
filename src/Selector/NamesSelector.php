<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Selector;

final class NamesSelector implements Selector
{
    /**
     * @var array<string, string>
     */
    private array $columns;

    /**
     * @param array<string> $columns List of column names to select.
     *                               String keys are used as aliases in a returned result.
     */
    public function __construct(array $columns)
    {
        if (empty($columns)) {
            throw new \InvalidArgumentException('Columns map should not be empty');
        }

        $this->columns = $this->normalizeColumnsMap($columns);
    }

    public function apply(array $row): array
    {
        return $this->compile($row)->apply($row);
    }

    public function compile(array $row): CompiledSelector
    {
        return new CompiledSelector($this->columns);
    }

    /**
     * @param array<string> $columns
     * @return array<string, string>
     */
    private function normalizeColumnsMap(array $columns): array
    {
        $result = [];

        foreach ($columns as $alias => $name) {
            if (!is_string($alias)) {
                $alias = $name;
            }

            $result[$alias] = $name;
        }

        return $result;
    }
}
