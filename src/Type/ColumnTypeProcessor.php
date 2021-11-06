<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Type;

use Smelesh\ResultSetMapper\Internal\DotPath;

/**
 * Converts database column values into PHP representation.
 */
final class ColumnTypeProcessor
{
    /**
     * @param array<string, string> $types Map of column path in dot notation to its type.
     *                                     When the column is omitted its value will be returned as is.
     *                                     Path examples: "id", "products.id", "user.id"
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
                    fn(mixed $value): mixed => $this->typeConverter->convert($value, $type)
                );
            }

            yield $row;
        }
    }
}
