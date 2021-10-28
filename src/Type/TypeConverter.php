<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Type;

interface TypeConverter
{
    /**
     * Convert raw column value from database into PHP representation.
     *
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    public function convert(mixed $value, string $type): mixed;
}
