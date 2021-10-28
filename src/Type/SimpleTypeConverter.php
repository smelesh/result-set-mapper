<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Type;

final class SimpleTypeConverter implements TypeConverter
{
    public function convert(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            'string' => (string) $value,
            default => throw new \InvalidArgumentException(sprintf('Unknown type "%s"', $type)),
        };
    }
}
