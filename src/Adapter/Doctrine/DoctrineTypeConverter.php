<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Adapter\Doctrine;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Smelesh\ResultSetMapper\Type\TypeConverter;

final class DoctrineTypeConverter implements TypeConverter
{
    public function __construct(private AbstractPlatform $platform)
    {
    }

    public function convert(mixed $value, string $type): mixed
    {
        try {
            $result = Type::getType($type)->convertToPHPValue($value, $this->platform);
        } catch (ConversionException $e) {
            throw new \UnexpectedValueException(sprintf(
                'Unable to convert value of type "%s" to type "%s"',
                get_debug_type($value),
                $type
            ), 0, $e);
        } catch (Exception $e) {
            throw new \InvalidArgumentException(sprintf('Unknown type "%s"', $type), 0, $e);
        }

        return $result;
    }
}
