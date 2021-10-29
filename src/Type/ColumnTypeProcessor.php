<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Type;

/**
 * Converts database column values into PHP representation.
 */
final class ColumnTypeProcessor
{
    /**
     * @var array<string, string|array>
     */
    private array $types;

    /**
     * @param array<string, string> $types Map of column path in dot notation to its type.
     *                                     When the column is omitted its value will be returned as is.
     *                                     Path examples: "id", "products.id", "user.id"
     */
    public function __construct(private TypeConverter $typeConverter, array $types)
    {
        $this->types = $this->normalizeTypesMap($types);
    }

    public function __invoke(\Traversable $rows): \Traversable
    {
        foreach ($rows as $row) {
            $this->mapTypesForItem($row, $this->types);

            yield $row;
        }
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, string|array> $types
     * @return void
     */
    private function mapTypesForItem(array &$item, array $types): void
    {
        foreach ($types as $path => $type) {
            $this->mapTypeForItem($item, $path, $type);
        }
    }

    /**
     * @param list<array<string, mixed>> $list
     * @param array<string, string|array> $types
     * @return void
     */
    private function mapTypesForList(array &$list, array $types): void
    {
        foreach ($list as &$item) {
            $this->mapTypesForItem($item, $types);
            unset($item);
        }
    }

    /**
     * @param array<string, mixed> $item
     * @param string $path
     * @param string|array<string, string|array> $type
     * @return void
     */
    private function mapTypeForItem(array &$item, string $path, string|array $type): void
    {
        if (!array_key_exists($path, $item)) {
            return;
        }

        if (is_array($type)) {
            if (!is_array($item[$path])) {
                return;
            }

            if (array_is_list($item[$path])) {
                $this->mapTypesForList($item[$path], $type);

                return;
            }

            $this->mapTypesForItem($item[$path], $type);

            return;
        }

        $item[$path] = $this->typeConverter->convert($item[$path], $type);
    }

    /**
     * @param array<string, string> $types
     * @return array<string, string|array>
     */
    private function normalizeTypesMap(array $types): array
    {
        $normalizedTypes = [];

        foreach ($types as $path => $type) {
            $pathSegments = explode('.', $path);
            $lastSegment = array_pop($pathSegments);

            $subTypes = &$normalizedTypes;

            foreach ($pathSegments as $pathSegment) {
                if (!isset($subTypes[$pathSegment]) || !is_array($subTypes[$pathSegment])) {
                    $subTypes[$pathSegment] = [];
                }

                $subTypes = &$subTypes[$pathSegment];
            }

            $subTypes[$lastSegment] = $type;
        }

        return $normalizedTypes;
    }
}
