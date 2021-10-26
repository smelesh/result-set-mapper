<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Node;

use PHPUnit\Framework\TestCase;
use Smelesh\ResultSetMapper\Node\Index;

class IndexTest extends TestCase
{
    public function testFindExistingRecord(): void
    {
        $index = new Index();
        $index->set(['id' => 1], ['user' => 'joe']);
        $index->set(['id' => 2], ['user' => 'tom']);
        $index->set(['id' => 3], ['user' => 'alice']);

        $this->assertSame(['user' => 'tom'], $index->find(['id' => 2]));
    }

    public function testFindNonExistentRecord(): void
    {
        $index = new Index();
        $index->set(['id' => 1], ['user' => 'joe']);

        $this->assertNull($index->find(['id' => 100]));
    }

    public function testReplaceExistingRecord(): void
    {
        $index = new Index();
        $index->set(['id' => 1], ['user' => 'joe']);

        $index->set(['id' => 1], ['user' => 'tom']);

        $this->assertSame(['user' => 'tom'], $index->find(['id' => 1]));
    }

    public function testFindFirst(): void
    {
        $index = new Index();
        $index->set(['id' => 1], ['user' => 'joe']);
        $index->set(['id' => 2], ['user' => 'tom']);

        $this->assertSame(['user' => 'joe'], $index->findFirst());
    }

    public function testFindFirstWithEmptyIndex(): void
    {
        $index = new Index();

        $this->assertNull($index->findFirst());
    }

    public function testFindAll(): void
    {
        $index = new Index();
        $index->set(['id' => 1], ['user' => 'joe']);
        $index->set(['id' => 2], ['user' => 'tom']);

        $this->assertSame([
            ['user' => 'joe'],
            ['user' => 'tom'],
        ], $index->findAll());
    }

    public function testFindAllWithEmptyIndex(): void
    {
        $index = new Index();

        $this->assertEmpty($index->findAll());
    }

    public function testNestedIndex(): void
    {
        $index = new Index();
        $index->set(['id' => 1], ['user' => 'joe']);
        $index->set(['id' => 2], ['user' => 'tom']);

        $nestedIndex = $index->nestedIndex('payments', ['id' => 1]);
        $nestedIndex->set(['id' => 1], ['method' => 'PAYPAL']);

        $this->assertSame(['method' => 'PAYPAL'], $nestedIndex->find(['id' => 1]));
        $this->assertSame(['user' => 'joe'], $index->find(['id' => 1]));
    }

    public function testNestedIndexReuseExistingIndex(): void
    {
        $index = new Index();
        $nestedIndex = $index->nestedIndex('payments', ['id' => 1]);

        $this->assertSame($nestedIndex, $index->nestedIndex('payments', ['id' => 1]));
        $this->assertNotSame($nestedIndex, $index->nestedIndex('payments', ['id' => 2]));
    }

    public function testNestedIndexShouldScopeRecordsByRelationOwner(): void
    {
        $index = new Index();

        $nestedIndex1 = $index->nestedIndex('payments', ['id' => 1]);
        $nestedIndex1->set(['id' => 10], ['method' => 'PAYPAL']);
        $nestedIndex1->set(['id' => 11], ['method' => 'CREDIT_CARD']);

        $nestedIndex2 = $index->nestedIndex('payments', ['id' => 2]);
        $nestedIndex2->set(['id' => 10], ['method' => 'GIFT']);

        $this->assertSame(['method' => 'PAYPAL'], $nestedIndex1->find(['id' => 10]));
        $this->assertSame(['method' => 'GIFT'], $nestedIndex2->find(['id' => 10]));
    }
}
