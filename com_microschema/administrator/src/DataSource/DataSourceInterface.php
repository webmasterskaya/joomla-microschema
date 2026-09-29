<?php

namespace Joomla\Component\Microschema\Administrator\DataSource;

interface DataSourceInterface
{
    public function getName(): string;

    public function getLabel(): string;

    public function supportsContext(string $context): bool;

    public function getType(): string;

    public function getValue(DataContext $context): mixed;
}
