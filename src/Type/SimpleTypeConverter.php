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

        try {
            $result = match ($type) {
                'int' => (int)$value,
                'float' => (float)$value,
                'bool' => (bool)$value,
                'string' => (string)$value,
                'datetime' => new \DateTimeImmutable($value),
                'json' => json_decode($value, true, flags: JSON_THROW_ON_ERROR),
                default => throw new \InvalidArgumentException(sprintf('Unknown type "%s"', $type)),
            };
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new \UnexpectedValueException(sprintf(
                'Unable to convert value of type "%s" to type "%s"',
                gettype($value),
                $type,
            ), 0, $e);
        }

        return $result;
    }
}
