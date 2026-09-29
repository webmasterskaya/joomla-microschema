<?php

namespace Joomla\Component\Microschema\Administrator\DataCollection;

final readonly class CollectionIteration
{
    public function __construct(
        public int $index,
        public int $position,
        public int $count,
        public int $total,
    ) {
    }
}
