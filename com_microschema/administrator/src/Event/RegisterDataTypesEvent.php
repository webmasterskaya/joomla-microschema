<?php

namespace Joomla\Component\Microschema\Administrator\Event;

use Joomla\CMS\Event\AbstractEvent;
use Joomla\Component\Microschema\Administrator\DataSource\DataTypeInterface;
use Joomla\Component\Microschema\Administrator\DataSource\DataTypeRegistry;

final class RegisterDataTypesEvent extends AbstractEvent
{
    public const NAME = 'onMicroschemaRegisterDataTypes';

    public function __construct(private readonly DataTypeRegistry $registry)
    {
        parent::__construct(self::NAME);
    }

    public function register(DataTypeInterface $type): void
    {
        $this->registry->register($type);
    }

    public function getRegistry(): DataTypeRegistry
    {
        return $this->registry;
    }
}
