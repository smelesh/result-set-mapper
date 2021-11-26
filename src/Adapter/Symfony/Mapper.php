<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Adapter\Symfony;

use Smelesh\ResultSetMapper\ResultSet;

interface Mapper
{
    /**
     * @template TValue
     * @param iterable<TValue> $rows
     * @return ResultSet<TValue>
     */
    public function createResultSet(iterable $rows): ResultSet;
}
