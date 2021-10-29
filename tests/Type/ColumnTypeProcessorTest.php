<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Type;

use Smelesh\ResultSetMapper\ResultSet;
use Smelesh\ResultSetMapper\Type\ColumnTypeProcessor;
use PHPUnit\Framework\TestCase;
use Smelesh\ResultSetMapper\Type\SimpleTypeConverter;

class ColumnTypeProcessorTest extends TestCase
{
    public function testProcessor(): void
    {
        $processor = new ColumnTypeProcessor(new SimpleTypeConverter(), [
            'id' => 'int',
            'unknown' => 'int',
        ]);

        $result = (new ResultSet([
            ['id' => 1, 'name' => 'user #1'],
            ['id' => 2, 'name' => 'user #2'],
        ]))->withProcessor($processor);

        $this->assertSame([
            ['id' => 1, 'name' => 'user #1'],
            ['id' => 2, 'name' => 'user #2'],
        ], $result->fetchAll());
    }
}
