<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Adapter\Symfony;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Smelesh\ResultSetMapper\Adapter\Doctrine\DoctrineTypeConverter;
use Smelesh\ResultSetMapper\ResultSet;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class DoctrineMapper implements Mapper
{
    public function __construct(private DenormalizerInterface $serializer, private Connection $connection)
    {
    }

    /**
     * @throws Exception
     */
    public function createResultSet(iterable $rows): ResultSet
    {
        return ResultSet::fromRows($rows)
            ->withDefaultHydrator(new SerializerHydrator($this->serializer))
            ->withDefaultTypeConverter(new DoctrineTypeConverter($this->connection->getDatabasePlatform()));
    }
}
