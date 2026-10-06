<?php

namespace Joomla\Component\Microschema\Administrator\Metadata;

final readonly class PropertyDefinition
{
    /**
     * @param list<string>          $types
     * @param array<string, string> $options language keys indexed by option values
     */
    public function __construct(
        public string $name,
        public array $types = ['string'],
        public bool $required = false,
        public bool $multiple = false,
        public ?string $tag = null,
        public bool $automatic = false,
        public array $options = [],
    ) {
    }
}
