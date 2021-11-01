<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Selector;

use PHPUnit\Framework\TestCase;
use Smelesh\ResultSetMapper\Selector\PrefixSelector;

class PrefixSelectorTest extends TestCase
{
    public function testCreateWithoutColumns(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Column prefix should not be empty');

        new PrefixSelector('');
    }

    public function testSelectColumnsWithPrefixRemoval(): void
    {
        $selector = new PrefixSelector('subscription_');

        $result = $selector->apply([
            'id' => 1,
            'subscription_id' => 10,
            'subscription_type' => 'PREMIUM',
        ]);

        $this->assertSame(['id' => 10, 'type' => 'PREMIUM'], $result);
    }

    public function testSelectColumnsWithPrefixReplacement(): void
    {
        $selector = new PrefixSelector('subscription_', 'subs_');

        $result = $selector->apply([
            'id' => 1,
            'subscription_id' => 10,
            'subscription_type' => 'PREMIUM',
        ]);

        $this->assertSame(['subs_id' => 10, 'subs_type' => 'PREMIUM'], $result);
    }

    public function testSelectColumnsWithoutReplacement(): void
    {
        $selector = new PrefixSelector('subscription_', false);

        $result = $selector->apply([
            'id' => 1,
            'subscription_id' => 10,
            'subscription_type' => 'PREMIUM',
        ]);

        $this->assertSame(['subscription_id' => 10, 'subscription_type' => 'PREMIUM'], $result);
    }
}
