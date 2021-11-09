<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Internal;

/**
 * Iterator that defers reading the next item until it's really requested.
 *
 * @internal
 *
 * @template TKey
 * @template TValue
 * @template-implements \IteratorAggregate<TKey, TValue>
 */
final class Iterator implements \IteratorAggregate
{
    private bool $initialized = false;

    /**
     * @param \Iterator<TKey, TValue> $iterator
     */
    public function __construct(private \Iterator $iterator)
    {
    }

    /**
     * @template TSourceKey
     * @template TSourceValue
     * @param iterable<TSourceKey, TSourceValue> $data
     * @return self<TSourceKey, TSourceValue>
     */
    public static function fromIterable(iterable $data)
    {
        if (is_array($data)) {
            $data = (static fn() => yield from $data)();
        }

        if ($data instanceof \Iterator) {
            return new self($data);
        }

        return new self(new \IteratorIterator($data));
    }

    /**
     * @return \Traversable<TKey, TValue>
     */
    public function getIterator(): \Traversable
    {
        return $this->iterator;
    }

    /**
     * @return TValue|null
     */
    public function current(): mixed
    {
        return $this->iterator->current();
    }

    public function advance(): bool
    {
        if ($this->initialized) {
            $this->iterator->next();
        } else {
            $this->iterator->rewind();
            $this->initialized = true;
        }

        return $this->iterator->valid();
    }
}
