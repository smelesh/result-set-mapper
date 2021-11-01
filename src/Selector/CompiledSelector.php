<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Selector;

/**
 * Optimized selector generated from concrete selector based on columns of the template row.
 * Use `Selector::compile` to generate an instance of this selector.
 *
 * @internal
 */
final class CompiledSelector implements Selector
{
    /**
     * @internal
     *
     * @param array<string, string> $columns Map of column names to select indexed by their aliases.
     */
    public function __construct(private array $columns)
    {
    }

    public function compile(array $row): self
    {
        return $this;
    }

    public function apply(array $row): array
    {
        $result = [];

        foreach ($this->columns as $alias => $name) {
            if (!array_key_exists($name, $row)) {
                throw new \LogicException(sprintf('Column "%s" does not exist', $name));
            }

            $result[$alias] = $row[$name];
        }

        return $result;
    }

    /**
     * Returns map of column names to select indexed by their aliases.
     *
     * @return array<string, string>
     */
    public function getSelectedColumnsMap(): array
    {
        return $this->columns;
    }
}
