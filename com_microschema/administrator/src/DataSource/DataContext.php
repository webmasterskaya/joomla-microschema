<?php

namespace Joomla\Component\Microschema\Administrator\DataSource;

use Joomla\Component\Microschema\Administrator\DataCollection\CollectionIteration;

final readonly class DataContext
{
    /** @param array<string, mixed> $fieldValues */
    public function __construct(
        public string $context,
        public int $itemId,
        public object|array $item,
        public array $fieldValues = [],
        public ?CollectionIteration $iteration = null,
    ) {
    }
}
