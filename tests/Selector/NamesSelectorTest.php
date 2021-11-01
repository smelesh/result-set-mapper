<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Selector;

use Smelesh\ResultSetMapper\Selector\NamesSelector;
use PHPUnit\Framework\TestCase;

class NamesSelectorTest extends TestCase
{
    public function testCreateWithoutColumns(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Columns map should not be empty');

        new NamesSelector([]);
    }

    public function testSelectColumns(): void
    {
        $selector = new NamesSelector([
            'id',
            'name',
        ]);

        $result = $selector->apply([
            'id' => 1,
            'name' => 'user #1',
            'country' => 'BY',
        ]);

        $this->assertSame(['id' => 1, 'name' => 'user #1'], $result);
    }

    public function testSelectColumnsWithAlias(): void
    {
        $selector = new NamesSelector([
            'id',
            'user_name' => 'name',
            'country',
        ]);

        $result = $selector->apply([
            'id' => 1,
            'name' => 'user #1',
            'country' => 'BY',
        ]);

        $this->assertSame(['id' => 1, 'user_name' => 'user #1', 'country' => 'BY'], $result);
    }

    public function testSelectUnknownColumn(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Column "unknown" does not exist');

        $selector = new NamesSelector([
            'id',
            'unknown',
        ]);

        $selector->apply([
            'id' => 1,
            'name' => 'user #1',
        ]);
    }
}
