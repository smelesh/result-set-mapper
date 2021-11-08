<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Processor;

use Smelesh\ResultSetMapper\Internal\DotPath;
use Smelesh\ResultSetMapper\Type\TypeConverter;

/**
 * Parses JSON serialized columns so their inner structures can be traversed by other processors.
 */
final class ParseJsonColumnsProcessor
{
    /**
     * @param list<string> $columns List of JSON columns to parse.
     */
    public function __construct(private array $columns)
    {
    }

    public function __invoke(\Traversable $rows): \Traversable
    {
        foreach ($rows as $row) {
            foreach ($this->columns as $column) {
                if (!isset($row[$column])) {
                    continue;
                }

                try {
                    $row[$column] = json_decode($row[$column], true, flags: JSON_THROW_ON_ERROR);
                } catch (\Throwable $e) {
                    throw new \RuntimeException(sprintf(
                        'Unable to parse JSON column "%s"',
                        $column,
                    ), 0, $e);
                }
            }

            yield $row;
        }
    }
}
