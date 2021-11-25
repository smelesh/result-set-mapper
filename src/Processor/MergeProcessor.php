<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Processor;

use Smelesh\ResultSetMapper\Internal\DotPath;

/**
 * Merges duplicate rows and embedded collections.
 *
 * When both items have the same distinct keys, the second item is merged into the first one
 * according to the following rules:
 * - non-array values are ignored;
 * - embedded objects are merged recursively;
 * - lists are appended (but not merged recursively!).
 *
 * When the path is specified, only items at this path of the current row will be affected.
 *
 * ATTENTION! Merge at the root path triggers FULL SCAN!
 */
final class MergeProcessor
{
    private string $path;
    private array $distinctBy;

    /**
     * @param string $path Path in "dot" notation to a collection of items that should be merged.
     *                     Empty string to merge root rows.
     * @param list<string> $distinctBy List of fields to check in an item to distinct from other items.
     *                                 Only scalar fields are allowed.
     */
    public function __construct(string $path, array $distinctBy)
    {
        if (empty($distinctBy)) {
            throw new \InvalidArgumentException('Distinct key should be provided');
        }

        $this->path = $path;
        $this->distinctBy = $distinctBy;
    }

    public function __invoke(\Traversable $rows): \Traversable
    {
        if ($this->path === '') {
            yield from $this->mergeDuplicateRows($rows);
        } else {
            foreach ($rows as $row) {
                DotPath::map(
                    $row,
                    $this->path,
                    /** @param list<array<string, mixed>> $items */
                    fn(array $items) => $this->mergeDuplicateRows($items)
                );

                yield $row;
            }
        }
    }

    /**
     * @param iterable<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function mergeDuplicateRows(iterable $rows): array
    {
        $index = [];

        foreach ($rows as $row) {
            $distinctHash = $this->buildDistinctHash($row);

            if (isset($index[$distinctHash])) {
                $index[$distinctHash] = $this->mergeRowsRecursively($index[$distinctHash], $row);
            } else {
                $index[$distinctHash] = $row;
            }
        }

        return array_values($index);
    }

    /**
     * @param array<string, mixed> $originRow
     * @param array<string, mixed> $duplicateRow
     * @return array<string, mixed>
     */
    private function mergeRowsRecursively(array $originRow, array $duplicateRow): array
    {
        foreach ($duplicateRow as $key => $value) {
            // ignore non-merging or missing fields
            if (!is_array($value) || !isset($originRow[$key]) || !is_array($originRow[$key])) {
                continue;
            }

            $isList = array_is_list($originRow[$key]);

            if ($isList) {
                if (!array_is_list($value)) {
                    continue;
                }

                // append collection items
                $originRow[$key] = array_merge($originRow[$key], $value);
            } else {
                if (array_is_list($value)) {
                    continue;
                }

                // merge embedded objects
                $originRow[$key] = $this->mergeRowsRecursively($originRow[$key], $value);
            }
        }

        return $originRow;
    }

    /**
     * @param array<string, mixed> $row
     * @return string
     */
    private function buildDistinctHash(array $row): string
    {
        $result = [];

        foreach ($this->distinctBy as $column) {
            $value = $row[$column] ?? null;

            if (!is_scalar($value)) {
                throw new \LogicException(sprintf(
                    'Only scalar values are allowed as distinct key, got "%s" at column "%s"',
                    get_debug_type($value),
                    $column
                ));
            }

            $result[$column] = $row[$column];
        }

        return implode("\0", $result);
    }
}
