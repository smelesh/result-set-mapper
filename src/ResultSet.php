<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper;

use Smelesh\ResultSetMapper\Hydrator\Hydrator;
use Smelesh\ResultSetMapper\Hydrator\SimpleHydrator;
use Smelesh\ResultSetMapper\Internal\Iterator;
use Smelesh\ResultSetMapper\Processor\ColumnTypeProcessor;
use Smelesh\ResultSetMapper\Processor\EmbeddedProcessor;
use Smelesh\ResultSetMapper\Processor\HydrateProcessor;
use Smelesh\ResultSetMapper\Processor\MergeProcessor;
use Smelesh\ResultSetMapper\Selector\NamesSelector;
use Smelesh\ResultSetMapper\Selector\PrefixSelector;
use Smelesh\ResultSetMapper\Selector\Selector;
use Smelesh\ResultSetMapper\Type\SimpleTypeConverter;
use Smelesh\ResultSetMapper\Type\TypeConverter;

/**
 * Result set provides the functionality to iterate and process rows.
 *
 * @template TValue
 */
final class ResultSet
{
    /**
     * @var Iterator<array-key, TValue>
     */
    private Iterator $rows;

    private ?TypeConverter $defaultTypeConverter = null;

    private ?Hydrator $defaultHydrator = null;

    /**
     * @param iterable<TValue> $rows
     */
    private function __construct(iterable $rows)
    {
        $this->rows = Iterator::fromIterable($rows);
    }

    /**
     * @template TSourceValue
     * @param iterable<TSourceValue> $rows
     * @return self<TSourceValue>
     */
    public static function fromRows(iterable $rows): self
    {
        return new self($rows);
    }

    /**
     * Decorates result set with rows processor.
     *
     * @template TNewValue
     * @param callable(\Traversable<TValue>):\Traversable<TNewValue> $processor
     * @return self<TNewValue>
     */
    public function withProcessor(callable $processor): self
    {
        return new self($processor($this->rows));
    }

    /**
     * Parses JSON serialized columns so their inner structures can be traversed by other processors.
     *
     * @see types
     *
     * @param list<string> $columns List of JSON columns to parse.
     * @return self<TValue>
     */
    public function parseJsonColumns(array $columns): self
    {
        return $this->types(array_fill_keys($columns, 'json'), new SimpleTypeConverter());
    }

    /**
     * Merges duplicate rows.
     * ATTENTION! Triggers FULL SCAN!
     *
     * @see MergeProcessor
     *
     * @param list<string> $distinctBy List of fields to check in a row to distinct from other rows.
     * @return self<TValue>
     */
    public function mergeRoot(array $distinctBy): self
    {
        return $this->withProcessor(new MergeProcessor('', $distinctBy));
    }

    /**
     * Merges embedded duplicate collections.
     *
     * @see MergeProcessor
     *
     * @param string $path Path in "dot" notation to a collection of items that should be merged.
     * @param list<string> $distinctBy List of fields to check in an item to distinct from other items.
     * @return self<TValue>
     */
    public function merge(string $path, array $distinctBy): self
    {
        return $this->withProcessor(new MergeProcessor($path, $distinctBy));
    }

    /**
     * Collects columns into an embedded item (hashmap) or a collection of items.
     *
     * @see EmbeddedProcessor
     *
     * @param string $path Path in "dot" notation to embedded result.
     * @param string|array<string>|Selector $columns Embeddable columns as a prefix, or columns map, or custom selector.
     * @param list<string> $preservedColumns List of embedded columns that should be kept at original position.
     * @return self<TValue>
     */
    public function embed(string $path, string|array|Selector $columns, array $preservedColumns = []): self
    {
        return $this->withProcessor(new EmbeddedProcessor(
            $path,
            $this->normalizeSelector($columns),
            $preservedColumns,
        ));
    }

    /**
     * Changes default type converter.
     * If not set, {@link SimpleTypeConverter} will be used by default.
     *
     * @return self<TValue>
     */
    public function withDefaultTypeConverter(TypeConverter $typeConverter): self
    {
        $result = new self($this->rows);
        $result->defaultTypeConverter = $typeConverter;

        return $result;
    }

    /**
     * Converts database column values into PHP representation.
     *
     * @see ColumnTypeProcessor
     *
     * @param array<string, string|callable(mixed):mixed> $types Map of column path in dot notation to its type.
     * @param TypeConverter|null $typeConverter Custom type converter, or `null` to use the default one.
     * @return self<TValue>
     */
    public function types(array $types, ?TypeConverter $typeConverter = null): self
    {
        return $this->withProcessor(new ColumnTypeProcessor(
            $typeConverter ?? $this->defaultTypeConverter ?? new SimpleTypeConverter(),
            $types,
        ));
    }

    /**
     * Changes default hydrator.
     * If not set, {@link SimpleHydrator} will be used by default.
     *
     * @return self<TValue>
     */
    public function withDefaultHydrator(Hydrator $hydrator): self
    {
        $result = new self($this->rows);
        $result->defaultHydrator = $hydrator;

        return $result;
    }

    /**
     * Converts each row into object of the given class.
     *
     * @see HydrateProcessor
     *
     * @template TNewValue
     * @param class-string<TNewValue> $targetClass
     * @return self<TNewValue>
     */
    public function hydrate(string $targetClass, ?Hydrator $hydrator = null): self
    {
        return $this->withProcessor(new HydrateProcessor(
            $hydrator ?? $this->defaultHydrator ?? new SimpleHydrator(),
            $targetClass,
        ));
    }

    /**
     * Returns the next row of the result set.
     *
     * @return TValue|null
     */
    public function fetch(): mixed
    {
        if (!$this->rows->advance()) {
            return null;
        }

        return $this->rows->current();
    }

    /**
     * Returns all rows from the result set.
     *
     * @return array<TValue>
     */
    public function fetchAll(): array
    {
        return iterator_to_array($this->rows);
    }

    /**
     * Returns iterator over result set rows.
     *
     * @return \Traversable<TValue>
     */
    public function iterate(): \Traversable
    {
        return $this->rows;
    }

    /**
     * @param string|array<string>|Selector $selector
     * @return Selector
     */
    private function normalizeSelector(string|array|Selector $selector): Selector
    {
        if ($selector instanceof Selector) {
            return $selector;
        }

        if (is_string($selector)) {
            return new PrefixSelector($selector);
        }

        return new NamesSelector($selector);
    }
}
