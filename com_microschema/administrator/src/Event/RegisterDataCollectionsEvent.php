<?php

namespace Joomla\Component\Microschema\Administrator\Event;

use Joomla\CMS\Event\AbstractEvent;
use Joomla\Component\Microschema\Administrator\DataCollection\DataCollectionInterface;
use Joomla\Component\Microschema\Administrator\DataCollection\DataCollectionRegistry;

final class RegisterDataCollectionsEvent extends AbstractEvent
{
    public const NAME = 'onMicroschemaRegisterDataCollections';

    public function __construct(private readonly DataCollectionRegistry $registry)
    {
        parent::__construct(self::NAME);
    }

    public function register(DataCollectionInterface $collection): void
    {
        $this->registry->register($collection);
    }

    public function getRegistry(): DataCollectionRegistry
    {
        return $this->registry;
    }
}
