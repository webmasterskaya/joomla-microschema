<?php

namespace Joomla\Component\Microschema\Administrator\Metadata;

abstract class AbstractDescriptor implements DescriptorInterface
{
    public function getFormat(): Format
    {
        return Format::JSON_LD;
    }

    /**
     * @param list<string> $types
     */
    final protected function property(
        string $name,
        array $types = ['string'],
        bool $required = false,
        bool $multiple = false,
        ?string $tag = null,
        bool $automatic = false,
    ): PropertyDefinition {
        return new PropertyDefinition($name, $types, $required, $multiple, $tag, $automatic);
    }
}
