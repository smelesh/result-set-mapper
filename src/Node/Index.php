<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Node;

/**
 * Data storage to index records by their primary key.
 * Used to merge duplicates of the same record.
 *
 * @internal
 */
final class Index
{
    /**
     * @var array<string, non-empty-array<string, mixed>>
     */
    private array $index = [];

    /**
     * @var array<string, array<string, self>>
     */
    private array $nestedIndexes = [];

    /**
     * Returns index for a relation of specific record.
     *
     * @param non-empty-array<string, mixed> $primaryKey Primary key of relation owner.
     */
    public function nestedIndex(string $name, array $primaryKey): self
    {
        return $this->nestedIndexes[$name][$this->hashPrimaryKey($primaryKey)] ??= new self();
    }

    /**
     * Sets a record by its primary key.
     *
     * @param non-empty-array<string, mixed> $primaryKey
     * @param non-empty-array<string, mixed> $record
     */
    public function set(array $primaryKey, array $record): void
    {
        $this->index[$this->hashPrimaryKey($primaryKey)] = $record;
    }

    /**
     * Returns a record by its primary key.
     *
     * @param non-empty-array<string, mixed> $primaryKey
     * @return non-empty-array<string, mixed>|null
     */
    public function find(array $primaryKey): ?array
    {
        return $this->index[$this->hashPrimaryKey($primaryKey)] ?? null;
    }

    /**
     * Returns all indexed records.
     *
     * @return list<non-empty-array<string, mixed>>
     */
    public function findAll(): array
    {
        return array_values($this->index);
    }

    /**
     * Returns the first record from the index.
     *
     * @return non-empty-array<string, mixed>|null
     */
    public function findFirst(): ?array
    {
        return reset($this->index) ?: null;
    }

    /**
     * @param non-empty-array<string, mixed> $primaryKey
     */
    private function hashPrimaryKey(array $primaryKey): string
    {
        $hash = '';

        foreach ($primaryKey as $column => $value) {
            $hash .= "\0{$column}\1{$value}";
        }

        return $hash;
    }
}
