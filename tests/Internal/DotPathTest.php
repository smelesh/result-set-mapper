<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Tests\Internal;

use Smelesh\ResultSetMapper\Internal\DotPath;
use PHPUnit\Framework\TestCase;

class DotPathTest extends TestCase
{
    public function testMapBySimplePath(): void
    {
        $data = ['id' => 1, 'name' => 'user #1'];

        DotPath::map($data, 'id', static fn(int $value) => $value + 10000);

        $this->assertSame([
            'id' => 10001,
            'name' => 'user #1',
        ], $data);
    }

    public function testMapByMissingPath(): void
    {
        $data = ['id' => 1, 'name' => 'user #1'];

        DotPath::map($data, 'unknown', static fn(int $value) => $value + 10000);

        $this->assertSame([
            'id' => 1,
            'name' => 'user #1',
        ], $data);
    }

    public function testMapByNestedPath(): void
    {
        $data = ['id' => 1, 'name' => 'user #1', 'subscription' => [
            'id' => 10, 'type' => 'PREMIUM',
        ]];

        DotPath::map($data, 'subscription.id', static fn(int $value) => $value + 10000);

        $this->assertSame(['id' => 1, 'name' => 'user #1', 'subscription' => [
            'id' => 10010, 'type' => 'PREMIUM',
        ]], $data);
    }

    public function testMapByNestedNonTraversablePath(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unexpected value at path "subscription.type", expected array, got "string"');

        $data = ['id' => 1, 'name' => 'user #1', 'subscription' => [
            'id' => 10, 'type' => 'PREMIUM',
        ]];

        DotPath::map($data, 'subscription.type.id', static fn(int $value) => $value + 10000);
    }

    public function testMapByNestedMissingPath(): void
    {
        $data = ['id' => 1, 'name' => 'user #1', 'subscription' => [
            'id' => 10, 'type' => 'PREMIUM',
        ]];

        DotPath::map($data, 'subscription.unknown', static fn(int $value) => $value + 10000);

        $this->assertSame(['id' => 1, 'name' => 'user #1', 'subscription' => [
            'id' => 10, 'type' => 'PREMIUM',
        ]], $data);
    }

    public function testMapByNestedPathWithEmptyEmbeddedItem(): void
    {
        $data = ['id' => 1, 'name' => 'user #1', 'subscription' => null];

        DotPath::map($data, 'subscription.id', static fn(int $value) => $value + 10000);

        $this->assertSame(['id' => 1, 'name' => 'user #1', 'subscription' => null], $data);
    }

    public function testMapByNestedPathWithEmptyEmbeddedCollection(): void
    {
        $data = ['id' => 1, 'name' => 'user #1', 'subscriptions' => []];

        DotPath::map($data, 'subscriptions[].id', static fn(int $value) => $value + 10000);

        $this->assertSame(['id' => 1, 'name' => 'user #1', 'subscriptions' => []], $data);
    }

    public function testMapByNestedExpandedPath(): void
    {
        $data = ['id' => 1, 'name' => 'user #1', 'subscriptions' => [
            ['id' => 10, 'type' => 'PREMIUM', 'features' => [
                ['id' => 'SUBTITLES', 'name' => 'Show subtitles'],
                ['id' => 'UPLOAD', 'name' => 'Allow uploads'],
            ]],
            ['id' => 20, 'type' => 'LITE', 'features' => [
                ['id' => 'ADD_COMMENT', 'name' => 'Add comments'],
            ]],
        ]];

        DotPath::map(
            $data,
            'subscriptions[].features',
            static fn(array $value) => array_merge($value, ['mapped' => true])
        );

        $this->assertSame(['id' => 1, 'name' => 'user #1', 'subscriptions' => [
            ['id' => 10, 'type' => 'PREMIUM', 'features' => [
                ['id' => 'SUBTITLES', 'name' => 'Show subtitles'],
                ['id' => 'UPLOAD', 'name' => 'Allow uploads'],
                'mapped' => true,
            ]],
            ['id' => 20, 'type' => 'LITE', 'features' => [
                ['id' => 'ADD_COMMENT', 'name' => 'Add comments'],
                'mapped' => true,
            ]],
        ]], $data);
    }

    public function testMapByNestedNonExpandableCollectionPath(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unexpected value at path "subscriptions[].features", expected array, got "string"');

        $data = ['id' => 1, 'name' => 'user #1', 'subscriptions' => [
            ['id' => 10, 'type' => 'PREMIUM', 'features' => 'SUBTITLES, UPLOAD'],
        ]];

        DotPath::map($data, 'subscriptions[].features[].id', static fn(string $value) => $value);
    }

    public function testMapByNestedNonExpandableObjectPath(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unexpected value at path "subscriptions[].features[]", expected array, got "string"');

        $data = ['id' => 1, 'name' => 'user #1', 'subscriptions' => [
            ['id' => 10, 'type' => 'PREMIUM', 'features' => ['SUBTITLES', 'UPLOAD']],
        ]];

        DotPath::map($data, 'subscriptions[].features[].id', static fn(string $value) => $value);
    }

    public function testMapByNestedExpandedPathWithExpandedResult(): void
    {
        $data = ['id' => 1, 'name' => 'user #1', 'subscriptions' => [
            ['id' => 10, 'type' => 'PREMIUM', 'features' => [
                ['id' => 'SUBTITLES', 'name' => 'Show subtitles'],
                ['id' => 'UPLOAD', 'name' => 'Allow uploads'],
            ]],
            ['id' => 20, 'type' => 'LITE', 'features' => [
                ['id' => 'ADD_COMMENT', 'name' => 'Add comments'],
            ]],
        ]];

        DotPath::map(
            $data,
            'subscriptions[].features[]',
            static fn(array $value) => array_merge($value, ['mapped' => true])
        );

        $this->assertSame(['id' => 1, 'name' => 'user #1', 'subscriptions' => [
            ['id' => 10, 'type' => 'PREMIUM', 'features' => [
                ['id' => 'SUBTITLES', 'name' => 'Show subtitles', 'mapped' => true],
                ['id' => 'UPLOAD', 'name' => 'Allow uploads', 'mapped' => true],
            ]],
            ['id' => 20, 'type' => 'LITE', 'features' => [
                ['id' => 'ADD_COMMENT', 'name' => 'Add comments', 'mapped' => true],
            ]],
        ]], $data);
    }

    public function testMapByNestedPathWithNonExpandableResult(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unexpected value at path "subscriptions[].features", expected array, got "string"');

        $data = ['id' => 1, 'name' => 'user #1', 'subscriptions' => [
            ['id' => 10, 'type' => 'PREMIUM', 'features' => 'SUBTITLES, UPLOAD'],
        ]];

        DotPath::map($data, 'subscriptions[].features[]', static fn(string $value) => $value);
    }

    public function testMapByRootPath(): void
    {
        $data = ['id' => 1, 'name' => 'user #1'];

        DotPath::map($data, '', static fn(array $value) => array_merge($value, ['mapped' => true]));

        $this->assertSame([
            'id' => 1,
            'name' => 'user #1',
            'mapped' => true,
        ], $data);
    }

    public function testMapByRootExpandedPath(): void
    {
        $data = [1, 2];

        DotPath::map($data, '[]', static fn(int $value) => $value + 10000);

        $this->assertSame([10001, 10002], $data);
    }
}
