<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Type;

/**
 * Converts database column values into PHP representation.
 */
final class ColumnTypeProcessor
{
    /**
     * @param array<string, string> $types Map of column path to its type.
     *                                     When the column is omitted its value will be returned as is.
     */
    public function __construct(private TypeConverter $typeConverter, private array $types)
    {
    }

    public function __invoke(\Traversable $rows): \Traversable
    {
        foreach ($rows as $row) {
            yield $this->mapRow($row);
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapRow(array $row): array
    {
        foreach ($this->types as $path => $type) {
            if (!array_key_exists($path, $row)) {
                continue;
            }

            $row[$path] = $this->typeConverter->convert($row[$path], $type);
        }

        return $row;
    }
}
