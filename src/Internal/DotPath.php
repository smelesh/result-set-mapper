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
        foreach (self::refs($data, self::parsePath($path)) as &$item) {
            $item = $mapper($item);
        }

        unset($item);
    }

    /**
     * Collects references to each value at the specified path.
     *
     * @param list<string> $parsedPath
     */
    private static function refs(mixed &$data, array $parsedPath, int $level = 0): array
    {
        if (!isset($parsedPath[$level])) {
            return [&$data];
        }

        if (!is_array($data)) {
            throw new \LogicException(sprintf(
                'Unexpected value at path "%s", expected array, got "%s"',
                self::buildPath($parsedPath, $level),
                gettype($data)
            ));
        }

        $field = $parsedPath[$level];

        if ($field === '[]') {
            $refs = [];

            foreach ($data as &$item) {
                $refs = array_merge($refs, self::refs($item, $parsedPath, $level + 1));
            }

            unset($item);

            return $refs;
        }

        if (!isset($data[$field])) {
            return [];
        }

        return self::refs($data[$field], $parsedPath, $level + 1);
    }

    /**
     * Parses path into a list of segments.
     *
     * @return list<string>
     */
    private static function parsePath(string $path): array
    {
        $segments = [];

        foreach (explode('.', $path) as $segment) {
            $expand = str_ends_with($segment, '[]');

            if ($expand) {
                $segment = substr($segment, 0, -2);
            }

            if ($segment !== '') {
                $segments[] = $segment;
            }

            if ($expand) {
                $segments[] = '[]';
            }
        }

        return $segments;
    }

    /**
     * Builds path from a list of segments.
     *
     * @param list<string> $segments
     */
    private static function buildPath(array $segments, int $level = -1): string
    {
        if ($level !== -1) {
            $segments = array_slice($segments, 0, $level);
        }

        $path = '';

        foreach ($segments as $segment) {
            if ($path !== '' && $segment !== '[]') {
                $path .= '.';
            }

            $path .= $segment;
        }

        return $path;
    }
}
