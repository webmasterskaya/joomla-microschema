<?php

namespace Joomla\Component\Microschema\Administrator\DataCollection;

use Joomla\Component\Microschema\Administrator\DataSource\DataContext;

final readonly class DataCollectionResult
{
    /** @param list<DataContext> $items */
    public function __construct(
        public array $items,
        public int $offset = 0,
        public ?int $total = null,
    ) {
    }
}
