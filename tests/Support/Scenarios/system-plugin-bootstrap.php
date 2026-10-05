<?php

declare(strict_types=1);

namespace Joomla\CMS\Event\Application {
    class AfterInitialiseEvent
    {
    }
}

namespace Joomla\CMS\Plugin {
    use Joomla\Event\DispatcherInterface;

    class CMSPlugin
    {
    }

    class PluginHelper
    {
        /** @var list<array{group: string, plugin: ?string, autoload: bool, dispatcher: DispatcherInterface}> */
        public static array $imports = [];

        public static function importPlugin(
            string $group,
            ?string $plugin = null,
            bool $autoload = true,
            ?DispatcherInterface $dispatcher = null,
        ): bool {
            self::$imports[] = compact('group', 'plugin', 'autoload', 'dispatcher');

            return true;
        }
    }
}

namespace Joomla\Event {
    interface DispatcherInterface
    {
    }

    interface DispatcherAwareInterface
    {
        public function setDispatcher(DispatcherInterface $dispatcher): void;

        public function getDispatcher(): DispatcherInterface;
    }

    trait DispatcherAwareTrait
    {
        private DispatcherInterface $dispatcher;

        public function setDispatcher(DispatcherInterface $dispatcher): void
        {
            $this->dispatcher = $dispatcher;
        }

        public function getDispatcher(): DispatcherInterface
        {
            return $this->dispatcher;
        }
    }

    interface SubscriberInterface
    {
        public static function getSubscribedEvents(): array;
    }
}

namespace {
    use Joomla\CMS\Event\Application\AfterInitialiseEvent;
    use Joomla\CMS\Plugin\PluginHelper;
    use Joomla\Event\DispatcherInterface;
    use Joomla\Plugin\System\Microschema\Extension\Microschema;

    define('_JEXEC', 1);

    require_once __DIR__ . '/../../../plugins/system/microschema/src/Extension/Microschema.php';

    function assertSystemBootstrapSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message);
        }
    }

    $subscriptions = Microschema::getSubscribedEvents();

    assertSystemBootstrapSame(
        'onAfterInitialise',
        $subscriptions['onAfterInitialise'] ?? null,
        'The system plugin must bootstrap integrations during application initialisation.',
    );
    assertSystemBootstrapSame(
        false,
        isset($subscriptions['onContentPrepareForm']),
        'The system plugin must not proxy native form events.',
    );
    assertSystemBootstrapSame(
        false,
        isset($subscriptions['onContentAfterSave']),
        'The system plugin must not proxy native save events.',
    );

    $dispatcher = new class () implements DispatcherInterface {
    };
    $reflection = new ReflectionClass(Microschema::class);
    /** @var Microschema $plugin */
    $plugin = $reflection->newInstanceWithoutConstructor();
    $plugin->setDispatcher($dispatcher);
    $plugin->onAfterInitialise(new AfterInitialiseEvent());

    assertSystemBootstrapSame('microschema', PluginHelper::$imports[0]['group'] ?? null, 'The integration group must load.');
    assertSystemBootstrapSame(
        true,
        array_key_exists('plugin', PluginHelper::$imports[0] ?? []),
        'The plugin filter must be passed explicitly.',
    );
    assertSystemBootstrapSame(null, PluginHelper::$imports[0]['plugin'], 'All integration plugins must load.');
    assertSystemBootstrapSame(true, PluginHelper::$imports[0]['autoload'] ?? null, 'Integration classes must autoload.');
    assertSystemBootstrapSame(
        $dispatcher,
        PluginHelper::$imports[0]['dispatcher'] ?? null,
        'Integrations must register on Joomla global dispatcher.',
    );

    echo "System plugin bootstrap tests passed.\n";
}
