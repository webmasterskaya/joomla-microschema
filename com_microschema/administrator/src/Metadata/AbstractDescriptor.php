<?php

namespace Joomla\Component\Microschema\Administrator\Metadata;

abstract class AbstractDescriptor implements DescriptorInterface
{
    public function getFormat(): Format
    {
        return Format::JSON_LD;
    }

    /**
     * @param list<string>          $types
     * @param array<string, string> $options language keys indexed by option values
     */
    final protected function property(
        string $name,
        array $types = ['string'],
        bool $required = false,
        bool $multiple = false,
        ?string $tag = null,
        bool $automatic = false,
        array $options = [],
    ): PropertyDefinition {
        return new PropertyDefinition($name, $types, $required, $multiple, $tag, $automatic, $options);
    }
}
