<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Stubs;

use Smelesh\ResultSetMapper\Type\TypeConverter;

final class VerboseTypeConverter implements TypeConverter
{
    public function convert(mixed $value, string $type): mixed
    {
        return sprintf('convert(%s, %s)', var_export($value, true), $type);
    }
}
