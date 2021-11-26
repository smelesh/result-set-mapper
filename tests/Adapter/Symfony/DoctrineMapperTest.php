<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Adapter\Symfony;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Smelesh\ResultSetMapper\Adapter\Symfony\DoctrineMapper;
use PHPUnit\Framework\TestCase;
use Smelesh\ResultSetMapper\Tests\Fixtures\UserDto;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class DoctrineMapperTest extends TestCase
{
    private Connection $connection;
    private Serializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new Serializer([
            new DateTimeNormalizer(),
            new PropertyNormalizer(null, new CamelCaseToSnakeCaseNameConverter()),
        ]);

        $this->connection = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);

        $this->prepareDatabaseSchema();
        $this->prepareFixtures();
    }

    public function testCreateResultSetToFetchAsArray(): void
    {
        $mapper = new DoctrineMapper($this->serializer, $this->connection);

        $result = $this->connection->executeQuery(<<<'SQL'
            SELECT id, name, created_at
            FROM users
            WHERE id = 1
        SQL);

        $user = $mapper->createResultSet($result->iterateAssociative())
            ->types([
                'id' => Types::INTEGER,
                'created_at' => Types::DATETIME_IMMUTABLE,
            ])
            ->fetch();

        $this->assertEquals([
            'id' => 1,
            'name' => 'user #1',
            'created_at' => new \DateTimeImmutable('2021-11-26 01:02:03'),
        ], $user);
    }

    public function testCreateResultSetToFetchAsObject(): void
    {
        $mapper = new DoctrineMapper($this->serializer, $this->connection);

        $result = $this->connection->executeQuery(<<<'SQL'
            SELECT id, name, created_at
            FROM users
            WHERE id = 1
        SQL);

        $user = $mapper->createResultSet($result->iterateAssociative())
            ->hydrate(UserDto::class)
            ->fetch();

        $this->assertEquals(new UserDto(
            1,
            'user #1',
            new \DateTimeImmutable('2021-11-26 01:02:03'),
        ), $user);
    }

    private function prepareDatabaseSchema(): void
    {
        $schema = $this->connection->createSchemaManager();

        $usersTable = new Table('users');
        $usersTable->addColumn('id', Types::INTEGER);
        $usersTable->addColumn('name', Types::STRING);
        $usersTable->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $usersTable->setPrimaryKey(['id']);

        $schema->createTable($usersTable);
    }

    private function prepareFixtures(): void
    {
        $this->connection->insert('users', [
            'id' => 1,
            'name' => 'user #1',
            'created_at' => new \DateTimeImmutable('2021-11-26 01:02:03'),
        ], [
            'created_at' => Types::DATETIME_IMMUTABLE,
        ]);
    }
}
