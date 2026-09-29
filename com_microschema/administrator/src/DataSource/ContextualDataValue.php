<?php

namespace Joomla\Component\Microschema\Administrator\DataSource;

final readonly class ContextualDataValue
{
    /**
     * @param array<string, mixed> $fieldValues
     * @param array<string, mixed> $overrides
     */
    public function __construct(
        public string $context,
        public int $itemId,
        public mixed $value,
        public array $fieldValues = [],
        public array $overrides = [],
    ) {
    }
}
