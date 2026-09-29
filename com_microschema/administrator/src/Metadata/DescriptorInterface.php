<?php

namespace Joomla\Component\Microschema\Administrator\Metadata;

interface DescriptorInterface
{
    public function getName(): string;

    public function getFormat(): Format;

    /** @return list<PropertyDefinition> */
    public function getProperties(): array;
}
