<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Processor;

use Smelesh\ResultSetMapper\Processor\ParseJsonColumnsProcessor;
use PHPUnit\Framework\TestCase;
use Smelesh\ResultSetMapper\ResultSet;

class ParseJsonColumnsProcessorTest extends TestCase
{
    public function testProcessor(): void
    {
        $processor = new ParseJsonColumnsProcessor(['subscription', 'payments']);

        $result = (new ResultSet([
            [
                'id' => '1',
                'subscription' => '{"id": 10, "type": "PREMIUM"}',
                'payments' => '[{"id": 1000, "amount": 1.99}, {"id": 1001, "amount": 0.99}]',
            ],
        ]))->withProcessor($processor);

        $this->assertSame([
            [
                'id' => '1',
                'subscription' => ['id' => 10, 'type' => 'PREMIUM'],
                'payments' => [
                    ['id' => 1000, 'amount' => 1.99],
                    ['id' => 1001, 'amount' => 0.99],
                ],
            ],
        ], $result->fetchAll());
    }

    public function testProcessorWithEmptyJson(): void
    {
        $processor = new ParseJsonColumnsProcessor(['subscription', 'payments']);

        $result = (new ResultSet([
            [
                'id' => '1',
                'subscription' => null,
                'payments' => '[]',
            ],
        ]))->withProcessor($processor);

        $this->assertSame([
            [
                'id' => '1',
                'subscription' => null,
                'payments' => [],
            ],
        ], $result->fetchAll());
    }

    public function testProcessorWithMissingColumns(): void
    {
        $processor = new ParseJsonColumnsProcessor(['unknown']);

        $result = (new ResultSet([
            [
                'id' => '1',
            ],
        ]))->withProcessor($processor);

        $this->assertSame([
            [
                'id' => '1',
            ],
        ], $result->fetchAll());
    }

    public function testProcessorWithInvalidJsonString(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to parse JSON column "invalid"');

        $processor = new ParseJsonColumnsProcessor(['invalid']);

        $result = (new ResultSet([
            [
                'id' => '1',
                'invalid' => 'invalid_json',
            ],
        ]))->withProcessor($processor);

        $result->fetchAll();
    }

    public function testProcessorWithNonJsonColumn(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to parse JSON column "invalid"');

        $processor = new ParseJsonColumnsProcessor(['invalid']);

        $result = (new ResultSet([
            [
                'id' => '1',
                'invalid' => true,
            ],
        ]))->withProcessor($processor);

        $result->fetchAll();
    }
}
