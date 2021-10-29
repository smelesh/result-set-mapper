<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Parser;

use Smelesh\ResultSetMapper\Node\AggregateRootNode;
use Smelesh\ResultSetMapper\Node\EmbeddedItemNode;
use Smelesh\ResultSetMapper\Parser\ParserProcessor;
use PHPUnit\Framework\TestCase;
use Smelesh\ResultSetMapper\ResultSet;

class ParserProcessorTest extends TestCase
{
    public function testProcessor(): void
    {
        $node = (new AggregateRootNode(['id', 'name'], ['id']))
            ->join('subscription', new EmbeddedItemNode(['id' => 'subscription_id', 'type' => 'subscription_type'], ['subscription_id']));

        $processor = new ParserProcessor($node);

        $result = (new ResultSet([
            ['id' => 1, 'name' => 'user #1', 'subscription_id' => 101, 'subscription_type' => 'PREMIUM'],
            ['id' => 2, 'name' => 'user #2', 'subscription_id' => 201, 'subscription_type' => 'LITE'],
        ]))->withProcessor($processor);

        $this->assertSame([
            ['id' => 1, 'name' => 'user #1', 'subscription' => ['id' => 101, 'type' => 'PREMIUM']],
            ['id' => 2, 'name' => 'user #2', 'subscription' => ['id' => 201, 'type' => 'LITE']],
        ], $result->fetchAll());
    }
}
