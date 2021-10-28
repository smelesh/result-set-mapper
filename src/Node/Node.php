<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Node;

use Smelesh\ResultSetMapper\Type\TypeConverter;

/**
 * Base marker for each parser node.
 */
interface Node
{
    /**
     * Registers type mapping which will be used to convert database values into PHP representation.
     * When the column is omitted its value will be returned as is.
     *
     * @param array<string, string> $types Map of column name to its type.
     */
    public function types(array $types, TypeConverter $typeConverter): self;
}
