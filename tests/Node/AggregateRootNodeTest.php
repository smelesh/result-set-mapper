<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Node;

use Smelesh\ResultSetMapper\Node\AggregateRootNode;
use PHPUnit\Framework\TestCase;
use Smelesh\ResultSetMapper\Node\EmbeddedCollectionNode;
use Smelesh\ResultSetMapper\Node\EmbeddedItemNode;

class AggregateRootNodeTest extends TestCase
{
    public function testCreateNodeWithoutColumns(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Column names should be provided');

        new AggregateRootNode([], ['id']);
    }

    public function testCreateNodeWithoutPrimaryKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Primary key should be provided');

        new AggregateRootNode(['id', 'name'], []);
    }

    public function testParseRowsWithSpecifiedColumns(): void
    {
        $node = new AggregateRootNode(['name', 'country'], ['id']);

        $result = $node->parseRows(new \ArrayIterator([
            ['id' => 1, 'name' => 'user #1', 'country' => 'BY', 'lang' => 'be'],
            ['id' => 2, 'name' => 'user #2', 'country' => 'US', 'lang' => 'en'],
        ]));

        $this->assertSame([
            ['name' => 'user #1', 'country' => 'BY'],
            ['name' => 'user #2', 'country' => 'US'],
        ], iterator_to_array($result));
    }

    public function testParseRowsWithColumnAlias(): void
    {
        $node = new AggregateRootNode(['id', 'user_name' => 'name'], ['id']);

        $result = $node->parseRows(new \ArrayIterator([
            ['id' => 1, 'name' => 'user #1'],
            ['id' => 2, 'name' => 'user #2'],
        ]));

        $this->assertSame([
            ['id' => 1, 'user_name' => 'user #1'],
            ['id' => 2, 'user_name' => 'user #2'],
        ], iterator_to_array($result));
    }

    public function testParseRowsWithUnknownColumn(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Column "unknown" does not exist in result set');

        $node = new AggregateRootNode(['id', 'unknown'], ['id']);

        $result = $node->parseRows(new \ArrayIterator([
            ['id' => 1, 'name' => 'user #1'],
        ]));

        iterator_to_array($result);
    }

    public function testParseRowsWithUnknownPrimaryKey(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Column "unknown" does not exist in result set');

        $node = new AggregateRootNode(['id', 'name'], ['unknown']);

        $result = $node->parseRows(new \ArrayIterator([
            ['id' => 1, 'name' => 'user #1'],
        ]));

        iterator_to_array($result);
    }

    public function testParseRowsWithDuplicates(): void
    {
        $node = new AggregateRootNode(['name', 'country'], ['id']);

        $result = $node->parseRows(new \ArrayIterator([
            ['id' => 1, 'name' => 'user #1', 'country' => 'BY'],
            ['id' => 2, 'name' => 'user #2', 'country' => 'US'],
            ['id' => 2, 'name' => 'user #2', 'country' => 'US'],
            ['id' => 3, 'name' => 'user #1', 'country' => 'BY'],
        ]));

        $this->assertSame([
            ['name' => 'user #1', 'country' => 'BY'], // id = 1
            ['name' => 'user #2', 'country' => 'US'], // id = 2
            ['name' => 'user #1', 'country' => 'BY'], // id = 3
        ], iterator_to_array($result));
    }

    public function testParseEmptyRowsList(): void
    {
        $node = new AggregateRootNode(['id', 'name'], ['id']);

        $result = $node->parseRows(new \ArrayIterator());

        $this->assertEmpty(iterator_to_array($result));
    }

    public function testParseNullRow(): void
    {
        $node = new AggregateRootNode(['id', 'name'], ['id']);

        $result = $node->parseRows(new \ArrayIterator([
            ['id' => null, 'name' => 'user #1'],
        ]));

        $this->assertEmpty(iterator_to_array($result));
    }

    public function testParseRowsWithRelations(): void
    {
        $node = (new AggregateRootNode(['id', 'name'], ['id']))
            ->join('subscription', new EmbeddedItemNode(['id' => 'subscription_id', 'type' => 'subscription_type'], ['subscription_id']))
            ->join('payments', new EmbeddedCollectionNode(['id' => 'payment_id', 'method' => 'payment_method'], ['payment_id']));

        $result = $node->parseRows(new \ArrayIterator([
            [
                'id' => 1, 'name' => 'user #1',
                'subscription_id' => 101, 'subscription_type' => 'PREMIUM',
                'payment_id' => 1001, 'payment_method' => 'PAYPAL',
            ],
            [
                'id' => 1, 'name' => 'user #1',
                'subscription_id' => 101, 'subscription_type' => 'PREMIUM',
                'payment_id' => 1002, 'payment_method' => 'CREDIT_CARD',
            ],
            [
                'id' => 2, 'name' => 'user #2',
                'subscription_id' => 201, 'subscription_type' => 'LITE',
                'payment_id' => 2001, 'payment_method' => 'PAYPAL',
            ],
        ]));

        $this->assertSame([
            [
                'id' => 1,
                'name' => 'user #1',
                'subscription' => ['id' => 101, 'type' => 'PREMIUM'],
                'payments' => [
                    ['id' => 1001, 'method' => 'PAYPAL'],
                    ['id' => 1002, 'method' => 'CREDIT_CARD'],
                ],
            ],
            [
                'id' => 2,
                'name' => 'user #2',
                'subscription' => ['id' => 201, 'type' => 'LITE'],
                'payments' => [
                    ['id' => 2001, 'method' => 'PAYPAL'],
                ],
            ],
        ], iterator_to_array($result));
    }

    public function testParseRowsWithEmbeddedEmptySingleItem(): void
    {
        $node = (new AggregateRootNode(['id', 'name'], ['id']))
            ->join('subscription', new EmbeddedItemNode(['id' => 'subscription_id', 'type' => 'subscription_type'], ['subscription_id']));

        $result = $node->parseRows(new \ArrayIterator([
            [
                'id' => 1, 'name' => 'user #1',
                'subscription_id' => null, 'subscription_type' => null,
            ],
            [
                'id' => 2, 'name' => 'user #2',
                'subscription_id' => 201, 'subscription_type' => 'LITE',
            ],
        ]));

        $this->assertSame([
            [
                'id' => 1,
                'name' => 'user #1',
                'subscription' => null,
            ],
            [
                'id' => 2,
                'name' => 'user #2',
                'subscription' => ['id' => 201, 'type' => 'LITE'],
            ],
        ], iterator_to_array($result));
    }

    public function testParseRowsWithEmbeddedEmptyCollectionItem(): void
    {
        $node = (new AggregateRootNode(['id', 'name'], ['id']))
            ->join('payments', new EmbeddedCollectionNode(['id' => 'payment_id', 'method' => 'payment_method'], ['payment_id']));

        $result = $node->parseRows(new \ArrayIterator([
            [
                'id' => 1, 'name' => 'user #1',
                'payment_id' => null, 'payment_method' => null,
            ],
            [
                'id' => 1, 'name' => 'user #1',
                'payment_id' => 1001, 'payment_method' => 'PAYPAL',
            ],
            [
                'id' => 2, 'name' => 'user #2',
                'payment_id' => null, 'payment_method' => null,
            ],
        ]));

        $this->assertSame([
            [
                'id' => 1,
                'name' => 'user #1',
                'payments' => [
                    ['id' => 1001, 'method' => 'PAYPAL'],
                ],
            ],
            [
                'id' => 2,
                'name' => 'user #2',
                'payments' => [],
            ],
        ], iterator_to_array($result));
    }

    public function testParseRowsWithNestedRelations(): void
    {
        $node = (new AggregateRootNode(['id', 'name'], ['id']))
            ->join('subscription', (new EmbeddedItemNode(['id' => 'subscription_id', 'type' => 'subscription_type'], ['subscription_id']))
                ->join('features', new EmbeddedCollectionNode(['id' => 'feature_id', 'name' => 'feature_name'], ['feature_id']))
            )
            ->join('payments', new EmbeddedCollectionNode(['id' => 'payment_id', 'method' => 'payment_method'], ['payment_id']));

        $result = $node->parseRows(new \ArrayIterator([
            [ // user 1, payment 1, feature 1
                'id' => 1, 'name' => 'user #1',
                'subscription_id' => 101, 'subscription_type' => 'PREMIUM',
                'feature_id' => 'SUBTITLES', 'feature_name' => 'Show subtitles',
                'payment_id' => 1001, 'payment_method' => 'PAYPAL',
            ],
            [ // user 1, payment 1, feature 2
                'id' => 1, 'name' => 'user #1',
                'subscription_id' => 101, 'subscription_type' => 'PREMIUM',
                'feature_id' => 'UPLOAD', 'feature_name' => 'Allow uploads',
                'payment_id' => 1001, 'payment_method' => 'PAYPAL',
            ],
            [ // user 1, payment 2, feature 1
                'id' => 1, 'name' => 'user #1',
                'subscription_id' => 101, 'subscription_type' => 'PREMIUM',
                'feature_id' => 'SUBTITLES', 'feature_name' => 'Show subtitles',
                'payment_id' => 1002, 'payment_method' => 'CREDIT_CARD',
            ],
            [ // user 1, payment 2, feature 2
                'id' => 1, 'name' => 'user #1',
                'subscription_id' => 101, 'subscription_type' => 'PREMIUM',
                'feature_id' => 'UPLOAD', 'feature_name' => 'Allow uploads',
                'payment_id' => 1002, 'payment_method' => 'CREDIT_CARD',
            ],
            [ // user 2
                'id' => 2, 'name' => 'user #2',
                'subscription_id' => 201, 'subscription_type' => 'LITE',
                'feature_id' => 'ADD_COMMENT', 'feature_name' => 'Add comments',
                'payment_id' => 2001, 'payment_method' => 'PAYPAL',
            ],
        ]));

        $this->assertSame([
            [
                'id' => 1,
                'name' => 'user #1',
                'subscription' => [
                    'id' => 101,
                    'type' => 'PREMIUM',
                    'features' => [
                        ['id' => 'SUBTITLES', 'name' => 'Show subtitles'],
                        ['id' => 'UPLOAD', 'name' => 'Allow uploads'],
                    ],
                ],
                'payments' => [
                    ['id' => 1001, 'method' => 'PAYPAL'],
                    ['id' => 1002, 'method' => 'CREDIT_CARD'],
                ],
            ],
            [
                'id' => 2,
                'name' => 'user #2',
                'subscription' => [
                    'id' => 201,
                    'type' => 'LITE',
                    'features' => [
                        ['id' => 'ADD_COMMENT', 'name' => 'Add comments'],
                    ],
                ],
                'payments' => [
                    ['id' => 2001, 'method' => 'PAYPAL'],
                ],
            ],
        ], iterator_to_array($result));
    }

    public function testParseRowsShouldNotMergeRelationsFromDifferentOwners(): void
    {
        $node = (new AggregateRootNode(['id', 'name'], ['id']))
            ->join('subscription', (new EmbeddedItemNode(['id' => 'subscription_id', 'type' => 'subscription_type'], ['subscription_id']))
                ->join('features', new EmbeddedCollectionNode(['id' => 'feature_id', 'name' => 'feature_name'], ['feature_id']))
            );

        $result = $node->parseRows(new \ArrayIterator([
            [ // user 1, subscription 1, feature 1
                'id' => 1, 'name' => 'user #1',
                'subscription_id' => 101, 'subscription_type' => 'PREMIUM',
                'feature_id' => 'SUBTITLES', 'feature_name' => 'Show subtitles',
            ],
            [ // user 1, subscription 1, feature 2
                'id' => 1, 'name' => 'user #1',
                'subscription_id' => 101, 'subscription_type' => 'PREMIUM',
                'feature_id' => 'UPLOAD', 'feature_name' => 'Allow uploads',
            ],
            [ // user 2, subscription 1, feature 3
                'id' => 2, 'name' => 'user #2',
                'subscription_id' => 101, 'subscription_type' => 'PREMIUM',
                'feature_id' => 'ADD_COMMENT', 'feature_name' => 'Add comments',
            ],
        ]));

        $this->assertSame([
            [
                'id' => 1,
                'name' => 'user #1',
                'subscription' => [
                    'id' => 101,
                    'type' => 'PREMIUM',
                    'features' => [
                        ['id' => 'SUBTITLES', 'name' => 'Show subtitles'],
                        ['id' => 'UPLOAD', 'name' => 'Allow uploads'],
                    ],
                ],
            ],
            [
                'id' => 2,
                'name' => 'user #2',
                'subscription' => [
                    'id' => 101,
                    'type' => 'PREMIUM',
                    'features' => [
                        ['id' => 'ADD_COMMENT', 'name' => 'Add comments'],
                    ],
                ],
            ],
        ], iterator_to_array($result));
    }

    public function testParseRowsWithEmptySingleItemWithNestedRelation(): void
    {
        $node = (new AggregateRootNode(['id', 'name'], ['id']))
            ->join('subscription', (new EmbeddedItemNode(['id' => 'subscription_id', 'type' => 'subscription_type'], ['subscription_id']))
                ->join('features', new EmbeddedCollectionNode(['id' => 'feature_id', 'name' => 'feature_name'], ['feature_id']))
            );

        $result = $node->parseRows(new \ArrayIterator([
            [
                'id' => 1, 'name' => 'user #1',
                'subscription_id' => null, 'subscription_type' => null,
                'feature_id' => null, 'feature_name' => null,
            ],
        ]));

        $this->assertSame([
            [
                'id' => 1,
                'name' => 'user #1',
                'subscription' => null,
            ],
        ], iterator_to_array($result));
    }

    public function testParseRowsWithEmptyCollectionItemWithNestedRelation(): void
    {
        $node = (new AggregateRootNode(['id', 'name'], ['id']))
            ->join('payments', (new EmbeddedCollectionNode(['id' => 'payment_id', 'method' => 'payment_method'], ['payment_id']))
                ->join('discount', new EmbeddedItemNode(['id' => 'discount_code', 'amount' => 'discount_amount'], ['discount_code']))
            );

        $result = $node->parseRows(new \ArrayIterator([
            [
                'id' => 1, 'name' => 'user #1',
                'payment_id' => null, 'payment_method' => null,
                'discount_code' => null, 'discount_amount' => null,
            ],
            [
                'id' => 2, 'name' => 'user #2',
                'payment_id' => 2001, 'payment_method' => 'PAYPAL',
                'discount_code' => null, 'discount_amount' => null,
            ],
        ]));

        $this->assertSame([
            [
                'id' => 1,
                'name' => 'user #1',
                'payments' => [],
            ],
            [
                'id' => 2,
                'name' => 'user #2',
                'payments' => [
                    ['id' => 2001, 'method' => 'PAYPAL', 'discount' => null],
                ],
            ],
        ], iterator_to_array($result));
    }
}
