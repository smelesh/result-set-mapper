<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Adapter\Symfony;

use Smelesh\ResultSetMapper\Hydrator\Hydrator;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class SerializerHydrator implements Hydrator
{
    public function __construct(private DenormalizerInterface $serializer)
    {
    }

    /**
     * @template T of object
     * @param class-string<T> $targetClass
     * @return T
     */
    public function hydrate(mixed $data, string $targetClass): object
    {
        try {
            $object = $this->serializer->denormalize($data, $targetClass);
        } catch (\Throwable $e) {
            throw new \RuntimeException(sprintf('Failed to hydrate to "%s"', $targetClass), 0, $e);
        }

        if (!$object instanceof $targetClass) {
            throw new \LogicException(sprintf(
                'Unexpected value returned from serializer, expected "%s", got "%s"',
                $targetClass,
                get_debug_type($object)
            ));
        }

        return $object;
    }
}
