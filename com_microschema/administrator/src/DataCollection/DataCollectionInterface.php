<?php

namespace Joomla\Component\Microschema\Administrator\DataCollection;

use Joomla\Component\Microschema\Administrator\DataSource\DataContext;

interface DataCollectionInterface
{
    public function getName(): string;

    public function getLabel(): string;

    public function supportsContext(string $context): bool;

    public function getPreviewContext(DataContext $context): DataContext;

    public function getItems(DataContext $context): DataCollectionResult;
}
