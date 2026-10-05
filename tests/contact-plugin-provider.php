<?php

declare(strict_types=1);

namespace Joomla\CMS\Extension {
    interface PluginInterface
    {
    }
}

namespace Joomla\CMS\MVC\Factory {
    interface MVCFactoryInterface
    {
        public function createModel(string $name, string $client, array $config): object;
    }
}

namespace Joomla\CMS\Extension\Service\Provider {
    use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
    use Joomla\Component\Microschema\Administrator\Model\ItemModel;
    use Joomla\DI\Container;
    use Joomla\DI\ServiceProviderInterface;

    final class MVCFactory implements ServiceProviderInterface
    {
        public function __construct(private readonly string $namespace)
        {
        }

        public function register(Container $container): void
        {
            $container->set(
                MVCFactoryInterface::class,
                new class () implements MVCFactoryInterface {
                    public function createModel(string $name, string $client, array $config): object
                    {
                        return new ItemModel();
                    }
                },
            );
        }
    }
}

namespace Joomla\CMS {
    final class Factory
    {
        public static object $application;

        public static function getApplication(): object
        {
            return self::$application;
        }
    }
}

namespace Joomla\Component\Microschema\Administrator\Model {
    use Joomla\CMS\User\User;

    class ItemModel
    {
        public ?User $currentUser = null;

        public function setCurrentUser(User $currentUser): void
        {
            $this->currentUser = $currentUser;
        }
    }
}

namespace Joomla\CMS\User {
    class User
    {
        public function __construct(public int $id = 0)
        {
        }
    }
}

namespace Joomla\CMS\Plugin {
    final class PluginHelper
    {
        public static function getPlugin(string $type, string $plugin): object
        {
            return (object) [
                'type'   => $type,
                'name'   => $plugin,
                'params' => '{}',
            ];
        }
    }
}

namespace Joomla\Event {
    interface DispatcherInterface
    {
    }
}

namespace Joomla\DI {
    interface ServiceProviderInterface
    {
        public function register(Container $container): void;
    }

    final class Container
    {
        /** @var array<string, mixed> */
        private array $services = [];

        public function set(string $id, mixed $service): void
        {
            $this->services[$id] = $service;
        }

        public function registerServiceProvider(ServiceProviderInterface $provider): void
        {
            $provider->register($this);
        }

        public function get(string $id): mixed
        {
            $service = $this->services[$id];

            if ($service instanceof \Closure) {
                $service = $service($this);
                $this->services[$id] = $service;
            }

            return $service;
        }
    }
}

namespace Joomla\Plugin\Microschema\Contact\Extension {
    use Joomla\CMS\Extension\PluginInterface;
    use Joomla\Component\Microschema\Administrator\Model\ItemModel;
    use Joomla\Event\DispatcherInterface;

    final class Contact implements PluginInterface
    {
        public ?object $application = null;
        public ?DispatcherInterface $dispatcher = null;

        /** @param array<string, mixed> $config */
        public function __construct(
            public readonly array $config,
            public readonly ItemModel $itemModel,
        ) {
        }

        public function setApplication(object $application): void
        {
            $this->application = $application;
        }

        public function setDispatcher(DispatcherInterface $dispatcher): void
        {
            $this->dispatcher = $dispatcher;
        }
    }
}

namespace {
    use Joomla\CMS\Extension\PluginInterface;
    use Joomla\CMS\Factory;
    use Joomla\CMS\User\User;
    use Joomla\Component\Microschema\Administrator\Model\ItemModel;
    use Joomla\DI\Container;
    use Joomla\Event\DispatcherInterface;
    use Joomla\Plugin\Microschema\Contact\Extension\Contact;

    define('_JEXEC', 1);

    $identity             = new User(99);
    $application          = new class ($identity) {
        public function __construct(private readonly User $identity)
        {
        }

        public function getIdentity(): User
        {
            return $this->identity;
        }
    };
    Factory::$application = $application;
    $dispatcher           = new class () implements DispatcherInterface {
    };
    $container            = new Container();
    $container->set(DispatcherInterface::class, $dispatcher);

    $provider = require __DIR__ . '/../plugins/microschema/contact/services/provider.php';
    $provider->register($container);

    $plugin = $container->get(PluginInterface::class);

    if (!$plugin instanceof Contact) {
        throw new RuntimeException('The provider must create the contact plugin.');
    }

    if (($plugin->config['type'] ?? null) !== 'microschema' || ($plugin->config['name'] ?? null) !== 'contact') {
        throw new RuntimeException('The provider must pass the contact plugin identity to CMSPlugin.');
    }

    if ($plugin->application !== $application || $plugin->dispatcher !== $dispatcher) {
        throw new RuntimeException('The provider must inject the application and dispatcher.');
    }

    if (!$plugin->itemModel instanceof ItemModel || $plugin->itemModel->currentUser !== $identity) {
        throw new RuntimeException('The provider must inject the configured MicroSchema item model.');
    }

    echo "Contact plugin provider tests passed.\n";
}
