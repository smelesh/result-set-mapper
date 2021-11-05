<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Embedded;

use Smelesh\ResultSetMapper\Embedded\MergeProcessor;
use PHPUnit\Framework\TestCase;
use Smelesh\ResultSetMapper\ResultSet;

class MergeProcessorTest extends TestCase
{
    public function testCreateWithoutDistinctKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Distinct key should be provided');

        new MergeProcessor('', []);
    }

    public function testMergeWithSimpleDistinctKey(): void
    {
        $processor = new MergeProcessor('', ['id']);

        $result = (new ResultSet([
            ['id' => 101, 'type' => 'PREMIUM'],
            ['id' => 102, 'type' => 'PREMIUM'],
            ['id' => 101, 'type' => 'PREMIUM'], // duplicate
        ]))->withProcessor($processor);

        $this->assertSame([
            ['id' => 101, 'type' => 'PREMIUM'],
            ['id' => 102, 'type' => 'PREMIUM'],
        ], $result->fetchAll());
    }

    public function testMergeWithCompositeDistinctKey(): void
    {
        $processor = new MergeProcessor('', ['id', 'app_id']);

        $result = (new ResultSet([
            ['id' => 101, 'app_id' => 'app1', 'type' => 'PREMIUM'],
            ['id' => 102, 'app_id' => 'app1', 'type' => 'PREMIUM'],
            ['id' => 101, 'app_id' => 'app1', 'type' => 'PREMIUM'], // duplicate
            ['id' => 101, 'app_id' => 'app2', 'type' => 'PREMIUM'],
        ]))->withProcessor($processor);

        $this->assertSame([
            ['id' => 101, 'app_id' => 'app1', 'type' => 'PREMIUM'],
            ['id' => 102, 'app_id' => 'app1', 'type' => 'PREMIUM'],
            ['id' => 101, 'app_id' => 'app2', 'type' => 'PREMIUM'],
        ], $result->fetchAll());
    }

    public function testMergeWithMissingDistinctKey(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Only scalar values are allowed as distinct key, got "NULL" at column "unknown"');

        $processor = new MergeProcessor('', ['unknown']);

        $result = (new ResultSet([
            ['id' => 101, 'type' => 'PREMIUM'],
        ]))->withProcessor($processor);

        $result->fetchAll();
    }

    public function testMergeWithNonScalarDistinctKey(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Only scalar values are allowed as distinct key, got "array" at column "options"');

        $processor = new MergeProcessor('', ['options']);

        $result = (new ResultSet([
            ['id' => 101, 'options' => ['PREMIUM']],
        ]))->withProcessor($processor);

        $result->fetchAll();
    }

    public function testMergeWithEmbeddedCollection(): void
    {
        $processor = new MergeProcessor('', ['id']);

        $result = (new ResultSet([
            ['id' => 101, 'type' => 'PREMIUM', 'features' => [
                ['id' => 'SUBTITLES', 'name' => 'Show subtitles'],
                ['id' => 'UPLOAD', 'name' => 'Allow uploads'],
            ]],
            ['id' => 101, 'type' => 'PREMIUM', 'features' => [ // duplicate
                ['id' => 'SUBTITLES', 'name' => 'Show subtitles'],
                ['id' => 'ADD_COMMENT', 'name' => 'Add comments'],
            ]],
            ['id' => 102, 'type' => 'PREMIUM', 'features' => [
                ['id' => 'SUBTITLES', 'name' => 'Show subtitles'],
            ]],
        ]))->withProcessor($processor);

        $this->assertSame([
            ['id' => 101, 'type' => 'PREMIUM', 'features' => [
                ['id' => 'SUBTITLES', 'name' => 'Show subtitles'],
                ['id' => 'UPLOAD', 'name' => 'Allow uploads'],
                ['id' => 'SUBTITLES', 'name' => 'Show subtitles'],
                ['id' => 'ADD_COMMENT', 'name' => 'Add comments'],
            ]],
            ['id' => 102, 'type' => 'PREMIUM', 'features' => [
                ['id' => 'SUBTITLES', 'name' => 'Show subtitles'],
            ]],
        ], $result->fetchAll());
    }

    public function testMergeWithEmbeddedItem(): void
    {
        $processor = new MergeProcessor('', ['id']);

        $result = (new ResultSet([
            ['id' => 101, 'type' => 'PREMIUM', 'product' => [
                'id' => 1001, 'name' => 'Premium Monthly', 'options' => ['SUBTITLES', 'UPLOAD'],
            ]],
            ['id' => 101, 'type' => 'PREMIUM', 'product' => [ // duplicate
                'id' => 1001, 'name' => 'Premium Monthly', 'options' => ['SUBTITLES', 'ADD_COMMENT'],
            ]],
            ['id' => 102, 'type' => 'PREMIUM', 'product' => [
                'id' => 1001, 'name' => 'Premium Monthly', 'options' => ['SUBTITLES'],
            ]],
        ]))->withProcessor($processor);

        $this->assertSame([
            ['id' => 101, 'type' => 'PREMIUM', 'product' => [
                'id' => 1001, 'name' => 'Premium Monthly', 'options' => ['SUBTITLES', 'UPLOAD', 'SUBTITLES', 'ADD_COMMENT'],
            ]],
            ['id' => 102, 'type' => 'PREMIUM', 'product' => [
                'id' => 1001, 'name' => 'Premium Monthly', 'options' => ['SUBTITLES'],
            ]],
        ], $result->fetchAll());
    }

    public function testMergeAtSpecificPath(): void
    {
        $processor = new MergeProcessor('subscriptions[].features', ['id']);

        $result = (new ResultSet([
            ['id' => 1, 'name' => 'user #1', 'subscriptions' => [
                ['id' => 101, 'type' => 'PREMIUM', 'features' => [
                    ['id' => 'SUBTITLES', 'name' => 'Show subtitles'],
                    ['id' => 'UPLOAD', 'name' => 'Allow uploads'],
                    ['id' => 'SUBTITLES', 'name' => 'Show subtitles'], // duplicate
                ]],
                ['id' => 101, 'type' => 'PREMIUM', 'features' => [
                    ['id' => 'UPLOAD', 'name' => 'Allow uploads'],
                    ['id' => 'UPLOAD', 'name' => 'Allow uploads'], // duplicate
                ]],
                ['id' => 102, 'type' => 'PREMIUM', 'features' => [
                    ['id' => 'SUBTITLES', 'name' => 'Show subtitles'],
                ]],
            ]],
            ['id' => 2, 'name' => 'user #2', 'subscriptions' => [
                ['id' => 101, 'type' => 'PREMIUM', 'features' => [
                    ['id' => 'SUBTITLES', 'name' => 'Show subtitles'],
                    ['id' => 'ADD_COMMENT', 'name' => 'Add comments'],
                    ['id' => 'ADD_COMMENT', 'name' => 'Add comments'], // duplicate
                ]],
            ]],
        ]))->withProcessor($processor);

        $this->assertSame([
            ['id' => 1, 'name' => 'user #1', 'subscriptions' => [
                ['id' => 101, 'type' => 'PREMIUM', 'features' => [
                    ['id' => 'SUBTITLES', 'name' => 'Show subtitles'],
                    ['id' => 'UPLOAD', 'name' => 'Allow uploads'],
                ]],
                ['id' => 101, 'type' => 'PREMIUM', 'features' => [
                    ['id' => 'UPLOAD', 'name' => 'Allow uploads'],
                ]],
                ['id' => 102, 'type' => 'PREMIUM', 'features' => [
                    ['id' => 'SUBTITLES', 'name' => 'Show subtitles'],
                ]],
            ]],
            ['id' => 2, 'name' => 'user #2', 'subscriptions' => [
                ['id' => 101, 'type' => 'PREMIUM', 'features' => [
                    ['id' => 'SUBTITLES', 'name' => 'Show subtitles'],
                    ['id' => 'ADD_COMMENT', 'name' => 'Add comments'],
                ]],
            ]],
        ], $result->fetchAll());
    }

    public function testMergeAtMissingPath(): void
    {
        $processor = new MergeProcessor('subscription.features', ['id']);

        $result = (new ResultSet([
            ['id' => 1, 'name' => 'user #1', 'subscription' => null],
            ['id' => 2, 'name' => 'user #2', 'subscription' => ['id' => 101, 'type' => 'PREMIUM', 'features' => []]],
        ]))->withProcessor($processor);

        $this->assertSame([
            ['id' => 1, 'name' => 'user #1', 'subscription' => null],
            ['id' => 2, 'name' => 'user #2', 'subscription' => ['id' => 101, 'type' => 'PREMIUM', 'features' => []]],
        ], $result->fetchAll());
    }
}
