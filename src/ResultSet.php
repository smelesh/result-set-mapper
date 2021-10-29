<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper;

/**
 * Result set provides the functionality to iterate and process rows.
 *
 * @template TKey
 * @template TValue
 */
final class ResultSet
{
    /**
     * @var \Iterator<TKey, TValue>
     */
    private \Iterator $rows;

    /**
     * @param iterable<TKey, TValue> $rows
     */
    public function __construct(iterable $rows = [])
    {
        $this->rows = new \IteratorIterator(is_array($rows) ? new \ArrayIterator($rows) : $rows);
        $this->rows->rewind();
    }

    /**
     * Decorates result set with rows processor.
     *
     * @param callable(\Traversable):\Traversable $processor
     * @return self<TKey, TValue>
     */
    public function withProcessor(callable $processor): self
    {
        return new self($processor($this->rows));
    }

    /**
     * Returns the next row of the result set.
     *
     * @return TValue|null
     */
    public function fetch(): mixed
    {
        if (!$this->rows->valid()) {
            return null;
        }

        $row = $this->rows->current();
        $this->rows->next();

        return $row;
    }

    /**
     * Returns all rows from the result set.
     *
     * @return array<TKey, TValue>
     */
    public function fetchAll(): array
    {
        return iterator_to_array($this->rows);
    }

    /**
     * Returns iterator over result set rows.
     *
     * @return \Traversable<TKey, TValue>
     */
    public function iterate(): \Traversable
    {
        return $this->rows;
    }
}
