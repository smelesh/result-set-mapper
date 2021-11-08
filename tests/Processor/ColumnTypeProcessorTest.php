<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Processor;

use Smelesh\ResultSetMapper\ResultSet;
use Smelesh\ResultSetMapper\Processor\ColumnTypeProcessor;
use PHPUnit\Framework\TestCase;
use Smelesh\ResultSetMapper\Type\SimpleTypeConverter;

class ColumnTypeProcessorTest extends TestCase
{
    public function testProcessor(): void
    {
        $processor = new ColumnTypeProcessor(new SimpleTypeConverter(), [
            'id' => 'int',
            'subscription.is_active' => 'bool',
            'payments[].id' => 'int',
            'payments[].invoice.amount' => 'float',
            'unknown' => 'int',
        ]);

        $result = ResultSet::fromRows([
            ['id' => '1', 'name' => 'user #1', 'subscription' => ['id' => 'SUB-1', 'is_active' => '1'], 'payments' => [
                ['id' => '1000', 'is_active' => '1', 'invoice' => ['id' => 'INVOICE-1', 'amount' => '1.99']],
            ]],
            ['id' => '2', 'name' => 'user #2', 'subscription' => null, 'payments' => []],
        ])->withProcessor($processor);

        $this->assertSame([
            ['id' => 1, 'name' => 'user #1', 'subscription' => ['id' => 'SUB-1', 'is_active' => true], 'payments' => [
                ['id' => 1000, 'is_active' => '1', 'invoice' => ['id' => 'INVOICE-1', 'amount' => 1.99]],
            ]],
            ['id' => 2, 'name' => 'user #2', 'subscription' => null, 'payments' => []],
        ], $result->fetchAll());
    }
}
