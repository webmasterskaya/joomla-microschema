<?php

declare(strict_types=1);

namespace Psr\Container {
    interface ContainerInterface
    {
        public function get(string $id): mixed;

        public function has(string $id): bool;
    }
}

namespace Joomla\CMS\Dispatcher {
    interface ComponentDispatcherFactoryInterface
    {
    }
}

namespace Joomla\CMS\Extension {
    use Psr\Container\ContainerInterface;

    interface BootableExtensionInterface
    {
        public function boot(ContainerInterface $container): void;
    }

    class MVCComponent
    {
        public function __construct(object $dispatcherFactory)
        {
        }
    }
}

namespace Joomla\CMS {
    class Factory
    {
        public static object $application;

        public static function getApplication(): object
        {
            return self::$application;
        }
    }
}

namespace Joomla\CMS\Plugin {
    use Joomla\Event\DispatcherInterface;

    class PluginHelper
    {
        /** @var list<array{group: string, dispatcher: DispatcherInterface}> */
        public static array $imports = [];

        public static function importPlugin(
            string $group,
            ?string $plugin = null,
            bool $autoload = true,
            ?DispatcherInterface $dispatcher = null,
        ): bool {
            self::$imports[] = ['group' => $group, 'dispatcher' => $dispatcher];

            return true;
        }
    }
}

namespace Joomla\Event {
    interface DispatcherInterface
    {
        public function dispatch(string $name, object $event): object;
    }
}

namespace Joomla\Component\Microschema\Administrator\Metadata {
    class MetadataRegistry
    {
    }
}

namespace Joomla\Component\Microschema\Administrator\DataSource {
    class DataSourceRegistry
    {
    }

    class DataTypeRegistry
    {
    }

    class DataSourceTemplateResolver
    {
    }
}

namespace Joomla\Component\Microschema\Administrator\DataCollection {
    class DataCollectionRegistry
    {
    }
}

namespace Joomla\Component\Microschema\Administrator\Schema {
    class SchemaCollector
    {
    }
}

namespace Joomla\Component\Microschema\Administrator\Integration {
    class IntegrationPluginRepository
    {
    }
}

namespace Joomla\Component\Microschema\Administrator\Event {
    use Joomla\Component\Microschema\Administrator\DataCollection\DataCollectionRegistry;
    use Joomla\Component\Microschema\Administrator\DataSource\DataSourceRegistry;
    use Joomla\Component\Microschema\Administrator\DataSource\DataTypeRegistry;
    use Joomla\Component\Microschema\Administrator\Metadata\MetadataRegistry;

    class RegisterMetadataEvent
    {
        public const NAME = 'onMicroschemaRegisterMetadata';

        public function __construct(public readonly MetadataRegistry $registry)
        {
        }
    }

    class RegisterDataSourcesEvent
    {
        public const NAME = 'onMicroschemaRegisterDataSources';

        public function __construct(public readonly DataSourceRegistry $registry)
        {
        }
    }

    class RegisterDataTypesEvent
    {
        public const NAME = 'onMicroschemaRegisterDataTypes';

        public function __construct(public readonly DataTypeRegistry $registry)
        {
        }
    }

    class RegisterDataCollectionsEvent
    {
        public const NAME = 'onMicroschemaRegisterDataCollections';

        public function __construct(public readonly DataCollectionRegistry $registry)
        {
        }
    }
}

namespace Joomla\Plugin\System\Microschema\Extension {
    use Joomla\Component\Microschema\Administrator\Event\RegisterDataSourcesEvent;
    use Joomla\Component\Microschema\Administrator\Event\RegisterDataTypesEvent;
    use Joomla\Component\Microschema\Administrator\Event\RegisterMetadataEvent;

    class Microschema
    {
        public int $metadataRegistrations = 0;

        public int $dataSourceRegistrations = 0;

        public int $dataTypeRegistrations = 0;

        public function registerMetadata(RegisterMetadataEvent $event): void
        {
            $this->metadataRegistrations++;
        }

        public function registerDataSources(RegisterDataSourcesEvent $event): void
        {
            $this->dataSourceRegistrations++;
        }

        public function registerDataTypes(RegisterDataTypesEvent $event): void
        {
            $this->dataTypeRegistrations++;
        }
    }
}

namespace {
    use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
    use Joomla\CMS\Factory;
    use Joomla\CMS\Plugin\PluginHelper;
    use Joomla\Component\Microschema\Administrator\DataSource\DataSourceRegistry;
    use Joomla\Component\Microschema\Administrator\DataCollection\DataCollectionRegistry;
    use Joomla\Component\Microschema\Administrator\DataSource\DataSourceTemplateResolver;
    use Joomla\Component\Microschema\Administrator\DataSource\DataTypeRegistry;
    use Joomla\Component\Microschema\Administrator\Event\RegisterDataSourcesEvent;
    use Joomla\Component\Microschema\Administrator\Event\RegisterDataCollectionsEvent;
    use Joomla\Component\Microschema\Administrator\Event\RegisterMetadataEvent;
    use Joomla\Component\Microschema\Administrator\Event\RegisterDataTypesEvent;
    use Joomla\Component\Microschema\Administrator\Extension\MicroschemaComponent;
    use Joomla\Component\Microschema\Administrator\Integration\IntegrationPluginRepository;
    use Joomla\Component\Microschema\Administrator\Metadata\MetadataRegistry;
    use Joomla\Component\Microschema\Administrator\Schema\SchemaCollector;
    use Joomla\Event\DispatcherInterface;
    use Joomla\Plugin\System\Microschema\Extension\Microschema as SystemPlugin;
    use Psr\Container\ContainerInterface;

    require_once __DIR__ . '/../../../com_microschema/administrator/src/Extension/MicroschemaComponent.php';

    function assertComponentDispatcherSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message);
        }
    }

    $dispatcherFactory = new class () implements ComponentDispatcherFactoryInterface {
    };
    $dispatcher = new class () implements DispatcherInterface {
        /** @var list<array{name: string, event: object}> */
        public array $events = [];

        public function dispatch(string $name, object $event): object
        {
            $this->events[] = compact('name', 'event');

            return $event;
        }
    };
    $corePlugin = new SystemPlugin();

    Factory::$application = new class ($corePlugin) {
        public function __construct(private readonly SystemPlugin $plugin)
        {
        }

        public function bootPlugin(string $plugin, string $group): object
        {
            return $this->plugin;
        }
    };

    $integrationRepository = new IntegrationPluginRepository();
    $component = new MicroschemaComponent(
        $dispatcherFactory,
        $dispatcher,
        new MetadataRegistry(),
        new DataSourceRegistry(),
        new DataTypeRegistry(),
        new DataCollectionRegistry(),
        new DataSourceTemplateResolver(),
        $integrationRepository,
        new SchemaCollector(),
    );
    $container = new class () implements ContainerInterface {
        public function get(string $id): mixed
        {
            throw new RuntimeException('The boot test does not use the container.');
        }

        public function has(string $id): bool
        {
            return false;
        }
    };

    $component->boot($container);
    $component->boot($container);

    assertComponentDispatcherSame('microschema', PluginHelper::$imports[0]['group'] ?? null, 'The component must load integrations.');
    assertComponentDispatcherSame(
        $dispatcher,
        PluginHelper::$imports[0]['dispatcher'] ?? null,
        'The component must register integrations on its injected global dispatcher.',
    );
    assertComponentDispatcherSame(1, count(PluginHelper::$imports), 'The component boot must be idempotent.');
    assertComponentDispatcherSame(1, $corePlugin->metadataRegistrations, 'Core metadata must register once.');
    assertComponentDispatcherSame(1, $corePlugin->dataSourceRegistrations, 'Core data sources must register once.');
    assertComponentDispatcherSame(1, $corePlugin->dataTypeRegistrations, 'Core data types must register once.');
    assertComponentDispatcherSame(
        $integrationRepository,
        $component->getIntegrationPluginRepository(),
        'The component must expose the repository from its own DI container.',
    );
    assertComponentDispatcherSame(
        [
            RegisterMetadataEvent::NAME,
            RegisterDataTypesEvent::NAME,
            RegisterDataSourcesEvent::NAME,
            RegisterDataCollectionsEvent::NAME,
        ],
        array_column($dispatcher->events, 'name'),
        'Registration events must be dispatched globally.',
    );

    $provider = file_get_contents(__DIR__ . '/../../../com_microschema/administrator/services/provider.php');

    assertComponentDispatcherSame(false, str_contains($provider, 'microschema.event.dispatcher'), 'The isolated dispatcher service must be removed.');
    assertComponentDispatcherSame(false, str_contains($provider, 'new Dispatcher('), 'The provider must not create a private dispatcher.');
    assertComponentDispatcherSame(
        true,
        str_contains($provider, '$container->get(DispatcherInterface::class)'),
        'The component must receive Joomla global dispatcher from the container.',
    );

    echo "Component global dispatcher tests passed.\n";
}
