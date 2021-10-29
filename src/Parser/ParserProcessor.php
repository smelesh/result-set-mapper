<?php

declare(strict_types=1);

namespace Smelesh\ResultSetMapper\Parser;

use Smelesh\ResultSetMapper\Node\RootNode;

final class ParserProcessor
{
    public function __construct(private RootNode $rootNode)
    {
    }

    public function __invoke(\Traversable $rows): \Traversable
    {
        yield from $this->rootNode->parseRows($rows);
    }
}
