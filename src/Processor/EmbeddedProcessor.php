<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Processor;

use Smelesh\ResultSetMapper\Internal\DotPath;
use Smelesh\ResultSetMapper\Selector\Selector;

/**
 * Collects columns into an embedded item (hashmap) or a collection of items.
 *
 * Nullable single items are converted to NULL.
 * Nullable collection items are skipped.
 */
final class EmbeddedProcessor
{
    private string $path;
    private Selector $columnsSelector;
    private array $preservedColumns;

    /**
     * @param string $path Embedded column name.
     * @param Selector $columnsSelector Selector to fetch columns for embedding.
     * @param list<string> $preservedColumns List of embedded columns that should be kept at original position.
     *                                       By default, all embedded columns are removed.
     */
    public function __construct(
        string $path,
        Selector $columnsSelector,
        array $preservedColumns = [],
    ) {
        if ($path === '') {
            throw new \InvalidArgumentException('Path should not be empty');
        }

        $this->path = $path;
        $this->columnsSelector = $columnsSelector;
        $this->preservedColumns = $preservedColumns;
    }

    public function __invoke(\Traversable $rows): \Traversable
    {
        $columnsSelector = null;
        $removedColumns = [];

        foreach ($rows as $row) {
            if ($columnsSelector === null) {
                $columnsSelector = $this->columnsSelector->compile($row);

                $removedColumns = array_values(array_diff(
                    $columnsSelector->getSelectedColumnsMap(),
                    $this->preservedColumns
                ));
            }

            $embedded = $columnsSelector->apply($row);

            foreach ($removedColumns as $column) {
                unset($row[$column]);
            }

            if ($this->isEmptyItem($embedded)) {
                $embedded = null;
            }

            DotPath::set($row, $this->path, $embedded);

            yield $row;
        }
    }

    private function isEmptyItem(array $item): bool
    {
        foreach ($item as $value) {
            if ($value !== null && $value !== []) {
                return false;
            }
        }

        return true;
    }
}
