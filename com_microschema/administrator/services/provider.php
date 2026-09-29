<?php

/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') || exit;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Component\Microschema\Administrator\DataCollection\DataCollectionRegistry;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceCatalogBuilder;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceRegistry;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceTemplateResolver;
use Joomla\Component\Microschema\Administrator\DataSource\DataTypeRegistry;
use Joomla\Component\Microschema\Administrator\Extension\MicroschemaComponent;
use Joomla\Component\Microschema\Administrator\Integration\IntegrationPluginRepository;
use Joomla\Component\Microschema\Administrator\Metadata\MetadataRegistry;
use Joomla\Component\Microschema\Administrator\Schema\SchemaCollector;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $debugLogger = defined('JDEBUG') && JDEBUG
            ? static function (string $message): void {
                Log::add($message, Log::DEBUG, 'com_microschema');
            }
        : null;

        $container->registerServiceProvider(new MVCFactory('\\Joomla\\Component\\Microschema'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Joomla\\Component\\Microschema'));

        $container->set(
            DataSourceRegistry::class,
            static fn (): DataSourceRegistry => new DataSourceRegistry(),
            true,
        );

        $container->set(
            DataTypeRegistry::class,
            static fn (): DataTypeRegistry => new DataTypeRegistry(),
            true,
        );

        $container->set(
            DataCollectionRegistry::class,
            static fn (): DataCollectionRegistry => new DataCollectionRegistry(),
            true,
        );

        $container->set(
            DataSourceCatalogBuilder::class,
            static fn (Container $container): DataSourceCatalogBuilder => new DataSourceCatalogBuilder(
                $container->get(DataSourceRegistry::class),
                $container->get(DataTypeRegistry::class),
            ),
            true,
        );

        $container->set(
            DataSourceTemplateResolver::class,
            static fn (Container $container): DataSourceTemplateResolver => new DataSourceTemplateResolver(
                $container->get(DataSourceRegistry::class),
                $container->get(DataTypeRegistry::class),
                $container->get(DataSourceCatalogBuilder::class),
                $container->get(DataCollectionRegistry::class),
                $debugLogger,
            ),
            true,
        );

        $container->set(
            MetadataRegistry::class,
            static fn (): MetadataRegistry => new MetadataRegistry(),
            true,
        );

        $container->set(
            SchemaCollector::class,
            static fn (): SchemaCollector => new SchemaCollector(),
            true,
        );

        $container->set(
            IntegrationPluginRepository::class,
            static fn (Container $container): IntegrationPluginRepository => new IntegrationPluginRepository(
                $container->get(DatabaseInterface::class),
            ),
            true,
        );

        $container->set(
            ComponentInterface::class,
            static function (Container $container): ComponentInterface {
                $component = new MicroschemaComponent(
                    $container->get(ComponentDispatcherFactoryInterface::class),
                    $container->get(DispatcherInterface::class),
                    $container->get(MetadataRegistry::class),
                    $container->get(DataSourceRegistry::class),
                    $container->get(DataTypeRegistry::class),
                    $container->get(DataCollectionRegistry::class),
                    $container->get(DataSourceTemplateResolver::class),
                    $container->get(IntegrationPluginRepository::class),
                    $container->get(SchemaCollector::class),
                );
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));

                return $component;
            }
        );
    }
};
