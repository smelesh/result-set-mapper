<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Hydrator;

/**
 * Simple hydrator to instantiate objects that implement {@link Hydratable} interface.
 */
final class SimpleHydrator implements Hydrator
{
    /**
     * @template T of object
     * @param class-string<T> $targetClass
     * @return T
     */
    public function hydrate(mixed $data, string $targetClass): object
    {
        if (!is_array($data)) {
            throw new \LogicException(sprintf(
                'Unable to hydrate from "%s", expected array',
                get_debug_type($data)
            ));
        }

        if (!class_exists($targetClass)) {
            throw new \LogicException(sprintf('Target class "%s" does not exist', $targetClass));
        }

        if (!is_subclass_of($targetClass, Hydratable::class)) {
            throw new \LogicException(sprintf(
                'Target class "%s" must be a subtype of "%s"',
                $targetClass,
                Hydratable::class,
            ));
        }

        try {
            $object = $targetClass::hydrate($data);
        } catch (\Throwable $e) {
            throw new \RuntimeException(sprintf('Failed to hydrate to "%s"', $targetClass), 0, $e);
        }

        // fix psalm type inference corrupted by is_subclass_of call
        /** @var class-string<T> $targetClass */

        if (!$object instanceof $targetClass) {
            throw new \LogicException(sprintf(
                'Unexpected value returned from object hydrator, expected "%s", got "%s"',
                $targetClass,
                get_debug_type($object)
            ));
        }

        return $object;
    }
}
