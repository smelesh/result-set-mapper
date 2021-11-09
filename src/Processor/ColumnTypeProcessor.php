<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Processor;

use Smelesh\ResultSetMapper\Internal\DotPath;
use Smelesh\ResultSetMapper\Type\TypeConverter;

/**
 * Converts database column values into PHP representation.
 */
final class ColumnTypeProcessor
{
    /**
     * @param array<string, string|callable(mixed):mixed> $types Map of column path in dot notation to its type.
     *                                                           When the column is omitted its value will be returned
     *                                                           as is.
     */
    public function __construct(private TypeConverter $typeConverter, private array $types)
    {
    }

    public function __invoke(\Traversable $rows): \Traversable
    {
        foreach ($rows as $row) {
            foreach ($this->types as $path => $type) {
                DotPath::map(
                    $row,
                    $path,
                    fn(mixed $value): mixed => $this->convertValue($value, $type)
                );
            }

            yield $row;
        }
    }

    /**
     * @param string|callable(mixed):mixed $type
     */
    private function convertValue(mixed $value, string|callable $type): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_callable($type)) {
            return $type($value);
        }

        return $this->typeConverter->convert($value, $type);
    }
}
