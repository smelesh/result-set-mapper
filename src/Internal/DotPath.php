<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Internal;

/**
 * Provides the functionality to access multi-dimensional array using dot notation.
 *
 * Examples:
 * - "id" equals to `$data['id']`
 * - "user.subscriptions" equals to `$data['user']['subscriptions']`
 * - "user.subscriptions[]" equals to `$data['user']['subscriptions'][<each>]`
 * - "user.subscriptions[].id" equals to `$data['user']['subscriptions'][<each>]['id']`
 * - "users[].subscriptions[]" equals to `$data['users'][<each>]['subscriptions'][<each>]`
 * - "" equals to `$data`
 * - "[]" equals to `$data[<each>]`
 *
 * @internal
 */
final class DotPath
{
    /**
     * Applies mapper to each value at the specified path.
     *
     * @internal
     *
     * @param array<string, mixed> $data
     * @param string $path
     * @param callable(mixed):mixed $mapper
     */
    public static function map(array &$data, string $path, callable $mapper): void
    {
        foreach (self::refs($data, $path) as &$item) {
            $item = $mapper($item);
        }

        unset($item);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function refs(array &$data, string $path, string $rootPath = ''): array
    {
        if (str_contains($path, '.')) {
            [$field, $nestedPath] = explode('.', $path, 2);
        } else {
            $field = $path;
            $nestedPath = null;
        }

        $fieldPath = ($rootPath !== '' ? $rootPath . '.' : '') . $field;

        if (str_ends_with($field, '[]')) {
            $field = substr($field, 0, -2);
            $expand = true;
        } else {
            $expand = false;
        }

        if ($field === '') {
            $value = &$data;
        } elseif (isset($data[$field])) {
            $value = &$data[$field];
        } else {
            return [];
        }

        if ($nestedPath !== null) {
            if ($expand) {
                if (!is_array($value)) {
                    throw new \LogicException(sprintf(
                        'Unexpected value at path "%s", expected array, got "%s"',
                        substr($fieldPath, 0, -2),
                        gettype($value)
                    ));
                }

                $refs = [];

                foreach ($value as &$item) {
                    if (!is_array($item)) {
                        throw new \LogicException(sprintf(
                            'Unexpected value at path "%s", expected array, got "%s"',
                            $fieldPath,
                            gettype($item)
                        ));
                    }

                    $refs = array_merge($refs, self::refs($item, $nestedPath, $fieldPath));
                }

                unset($item);
            } else {
                if (!is_array($value)) {
                    throw new \LogicException(sprintf(
                        'Unexpected value at path "%s", expected array, got "%s"',
                        $fieldPath,
                        gettype($value)
                    ));
                }

                $refs = self::refs($value, $nestedPath, $fieldPath);
            }

            return $refs;
        }

        if ($expand) {
            if (!is_array($value)) {
                throw new \LogicException(sprintf(
                    'Unexpected value at path "%s", expected array, got "%s"',
                    substr($fieldPath, 0, -2),
                    gettype($value)
                ));
            }

            $refs = [];

            foreach ($value as &$item) {
                $refs[] = &$item;
            }

            unset($item);
        } else {
            $refs = [&$value];
        }

        return $refs;
    }
}
