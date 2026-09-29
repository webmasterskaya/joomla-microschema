<?php

namespace Joomla\Component\Microschema\Administrator\Event;

use Joomla\CMS\Event\AbstractEvent;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceInterface;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceRegistry;

final class RegisterDataSourcesEvent extends AbstractEvent
{
    public const NAME = 'onMicroschemaRegisterDataSources';

    public function __construct(private readonly DataSourceRegistry $registry)
    {
        parent::__construct(self::NAME);
    }

    public function register(DataSourceInterface $source): void
    {
        $this->registry->register($source);
    }

    public function getRegistry(): DataSourceRegistry
    {
        return $this->registry;
    }
}
