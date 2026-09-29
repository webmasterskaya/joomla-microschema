<?php

namespace Joomla\Component\Microschema\Administrator\Extension;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Microschema\Administrator\DataCollection\DataCollectionRegistry;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceRegistry;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceTemplateResolver;
use Joomla\Component\Microschema\Administrator\DataSource\DataTypeRegistry;
use Joomla\Component\Microschema\Administrator\Event\RegisterDataCollectionsEvent;
use Joomla\Component\Microschema\Administrator\Event\RegisterDataSourcesEvent;
use Joomla\Component\Microschema\Administrator\Event\RegisterDataTypesEvent;
use Joomla\Component\Microschema\Administrator\Event\RegisterMetadataEvent;
use Joomla\Component\Microschema\Administrator\Integration\IntegrationPluginRepository;
use Joomla\Component\Microschema\Administrator\Metadata\MetadataRegistry;
use Joomla\Component\Microschema\Administrator\Schema\SchemaCollector;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\System\Microschema\Extension\Microschema as MicroschemaSystemPlugin;
use Psr\Container\ContainerInterface;

final class MicroschemaComponent extends MVCComponent implements BootableExtensionInterface
{
    private bool $metadataRegistered = false;

    public function __construct(
        ComponentDispatcherFactoryInterface $dispatcherFactory,
        private readonly DispatcherInterface $eventDispatcher,
        private readonly MetadataRegistry $metadataRegistry,
        private readonly DataSourceRegistry $dataSourceRegistry,
        private readonly DataTypeRegistry $dataTypeRegistry,
        private readonly DataCollectionRegistry $dataCollectionRegistry,
        private readonly DataSourceTemplateResolver $dataSourceTemplateResolver,
        private readonly IntegrationPluginRepository $integrationPluginRepository,
        private readonly SchemaCollector $schemaCollector,
    ) {
        parent::__construct($dispatcherFactory);
    }

    public function boot(ContainerInterface $container): void
    {
        if ($this->metadataRegistered) {
            return;
        }

        $corePlugin = Factory::getApplication()->bootPlugin('microschema', 'system');

        if (!$corePlugin instanceof MicroschemaSystemPlugin) {
            throw new \RuntimeException('The Microschema system plugin is unavailable.');
        }

        PluginHelper::importPlugin('microschema', null, true, $this->eventDispatcher);

        $metadataEvent = new RegisterMetadataEvent($this->metadataRegistry);
        $corePlugin->registerMetadata($metadataEvent);
        $this->eventDispatcher->dispatch(RegisterMetadataEvent::NAME, $metadataEvent);

        $dataTypesEvent = new RegisterDataTypesEvent($this->dataTypeRegistry);
        $corePlugin->registerDataTypes($dataTypesEvent);
        $this->eventDispatcher->dispatch(RegisterDataTypesEvent::NAME, $dataTypesEvent);

        $dataSourcesEvent = new RegisterDataSourcesEvent($this->dataSourceRegistry);
        $corePlugin->registerDataSources($dataSourcesEvent);
        $this->eventDispatcher->dispatch(RegisterDataSourcesEvent::NAME, $dataSourcesEvent);

        $dataCollectionsEvent = new RegisterDataCollectionsEvent($this->dataCollectionRegistry);
        $this->eventDispatcher->dispatch(RegisterDataCollectionsEvent::NAME, $dataCollectionsEvent);

        $this->metadataRegistered = true;
    }

    public function getMetadataRegistry(): MetadataRegistry
    {
        return $this->metadataRegistry;
    }

    public function getDataSourceRegistry(): DataSourceRegistry
    {
        return $this->dataSourceRegistry;
    }

    public function getDataTypeRegistry(): DataTypeRegistry
    {
        return $this->dataTypeRegistry;
    }

    public function getDataCollectionRegistry(): DataCollectionRegistry
    {
        return $this->dataCollectionRegistry;
    }

    public function getDataSourceTemplateResolver(): DataSourceTemplateResolver
    {
        return $this->dataSourceTemplateResolver;
    }

    public function getSchemaCollector(): SchemaCollector
    {
        return $this->schemaCollector;
    }

    public function getIntegrationPluginRepository(): IntegrationPluginRepository
    {
        return $this->integrationPluginRepository;
    }
}
