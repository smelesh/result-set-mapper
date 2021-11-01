<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Selector;

final class PrefixSelector implements Selector
{
    private string $prefix;
    private string $replaceTo;

    /**
     * @param string $prefix Column name prefix
     * @param string|false $replaceTo Prefix replacement or `false` to avoid renaming.
     */
    public function __construct(string $prefix, string|false $replaceTo = '')
    {
        if ($prefix === '') {
            throw new \InvalidArgumentException('Column prefix should not be empty');
        }

        $this->prefix = $prefix;
        $this->replaceTo = $replaceTo !== false ? $replaceTo : $prefix;
    }

    public function apply(array $row): array
    {
        return $this->compile($row)->apply($row);
    }

    public function compile(array $row): CompiledSelector
    {
        $columns = [];

        foreach ($row as $name => $value) {
            if (!str_starts_with($name, $this->prefix)) {
                continue;
            }

            $alias = substr($name, strlen($this->prefix));

            if ($alias === '') {
                continue;
            }

            $alias = $this->replaceTo . $alias;

            $columns[$alias] = $name;
        }

        return new CompiledSelector($columns);
    }
}
