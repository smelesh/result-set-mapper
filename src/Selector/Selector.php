<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Selector;

/**
 * Selector is used to filter specific columns from a row.
 */
interface Selector
{
    /**
     * Generates optimized selector based on the provided sample row.
     *
     * @param array<string, mixed> $row
     */
    public function compile(array $row): CompiledSelector;

    /**
     * Applies selector rules to a row and returns matched columns.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function apply(array $row): array;
}
