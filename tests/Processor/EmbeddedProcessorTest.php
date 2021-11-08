<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Processor;

use Smelesh\ResultSetMapper\Processor\EmbeddedProcessor;
use PHPUnit\Framework\TestCase;
use Smelesh\ResultSetMapper\ResultSet;
use Smelesh\ResultSetMapper\Selector\PrefixSelector;

class EmbeddedProcessorTest extends TestCase
{
    public function testProcessor(): void
    {
        $processor = new EmbeddedProcessor('subscription', new PrefixSelector('subscription_'));

        $result = ResultSet::fromRows([
            ['id' => 1, 'subscription_id' => 10, 'subscription_type' => 'PREMIUM'],
            ['id' => 2, 'subscription_id' => 20, 'subscription_type' => 'LITE'],
        ])->withProcessor($processor);

        $this->assertSame([
            ['id' => 1, 'subscription' => ['id' => 10, 'type' => 'PREMIUM']],
            ['id' => 2, 'subscription' => ['id' => 20, 'type' => 'LITE']],
        ], $result->fetchAll());
    }

    public function testProcessorWithCollection(): void
    {
        $processor = new EmbeddedProcessor(
            'subscriptions[]',
            new PrefixSelector('subscription_'),
        );

        $result = ResultSet::fromRows([
            ['id' => 1, 'subscription_id' => 10, 'subscription_type' => 'PREMIUM'],
            ['id' => 2, 'subscription_id' => 20, 'subscription_type' => 'LITE'],
        ])->withProcessor($processor);

        $this->assertSame([
            ['id' => 1, 'subscriptions' => [['id' => 10, 'type' => 'PREMIUM']]],
            ['id' => 2, 'subscriptions' => [['id' => 20, 'type' => 'LITE']]],
        ], $result->fetchAll());
    }

    public function testProcessorWithPreservedColumns(): void
    {
        $processor = new EmbeddedProcessor(
            'subscription',
            new PrefixSelector('subscription_'),
            ['subscription_id']
        );

        $result = ResultSet::fromRows([
            ['id' => 1, 'subscription_id' => 10, 'subscription_type' => 'PREMIUM'],
            ['id' => 2, 'subscription_id' => 20, 'subscription_type' => 'LITE'],
        ])->withProcessor($processor);

        $this->assertSame([
            ['id' => 1, 'subscription_id' => 10, 'subscription' => ['id' => 10, 'type' => 'PREMIUM']],
            ['id' => 2, 'subscription_id' => 20, 'subscription' => ['id' => 20, 'type' => 'LITE']],
        ], $result->fetchAll());
    }

    public function testProcessorWithEmptyItem(): void
    {
        $processor = new EmbeddedProcessor('subscription', new PrefixSelector('subscription_'));

        $result = ResultSet::fromRows([
            ['id' => 1, 'subscription_id' => null, 'subscription_type' => null, 'subscription_payments' => []],
        ])->withProcessor($processor);

        $this->assertSame([
            ['id' => 1, 'subscription' => null],
        ], $result->fetchAll());
    }

    public function testProcessorWithEmptyCollectionItem(): void
    {
        $processor = new EmbeddedProcessor(
            'subscriptions[]',
            new PrefixSelector('subscription_'),
        );

        $result = ResultSet::fromRows([
            ['id' => 1, 'subscription_id' => null, 'subscription_type' => null, 'subscription_payments' => []],
        ])->withProcessor($processor);

        $this->assertSame([
            ['id' => 1, 'subscriptions' => []],
        ], $result->fetchAll());
    }

    public function testProcessorWithPartiallyEmptyItem(): void
    {
        $processor = new EmbeddedProcessor('subscription', new PrefixSelector('subscription_'));

        $result = ResultSet::fromRows([
            ['id' => 1, 'subscription_id' => 10, 'subscription_type' => null, 'subscription_payments' => []],
        ])->withProcessor($processor);

        $this->assertSame([
            ['id' => 1, 'subscription' => ['id' => 10, 'type' => null, 'payments' => []]],
        ], $result->fetchAll());
    }

    public function testProcessorWithNestedEmbeddable(): void
    {
        $processor = new EmbeddedProcessor(
            'subscription.payments[]',
            new PrefixSelector('payment_'),
        );

        $result = ResultSet::fromRows([
            ['id' => 1, 'subscription' => ['id' => 10], 'payment_id' => 100, 'payment_type' => 'PAYPAL'],
            ['id' => 1, 'subscription' => ['id' => 10], 'payment_id' => 101, 'payment_type' => 'CREDIT_CARD'],
        ])->withProcessor($processor);

        $this->assertSame([
            ['id' => 1, 'subscription' => ['id' => 10, 'payments' => [
                ['id' => 100, 'type' => 'PAYPAL'],
            ]]],
            ['id' => 1, 'subscription' => ['id' => 10, 'payments' => [
                ['id' => 101, 'type' => 'CREDIT_CARD'],
            ]]],
        ], $result->fetchAll());
    }
}
