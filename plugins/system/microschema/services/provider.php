<?php

/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') || exit;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Component\Microschema\Administrator\Metadata\ExistingSocialMetadataChecker;
use Joomla\Component\Microschema\Administrator\Metadata\SocialMetadataInjector;
use Joomla\Component\Microschema\Administrator\Metadata\SocialMetadataRenderer;
use Joomla\Component\Microschema\Administrator\Schema\ExistingSchemaChecker;
use Joomla\Component\Microschema\Administrator\Schema\JsonLdRenderer;
use Joomla\Component\Microschema\Administrator\Schema\SchemaDateEnricher;
use Joomla\Component\Microschema\Administrator\Schema\SchemaIdentityEnricher;
use Joomla\Component\Microschema\Administrator\Schema\SchemaLanguageEnricher;
use Joomla\Component\Microschema\Administrator\Schema\SchemaMarkupInjector;
use Joomla\Component\Microschema\Administrator\Schema\SchemaResolver;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\System\Microschema\Extension\Microschema;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $debugLogger = defined('JDEBUG') && JDEBUG
            ? static function (string $message): void {
                Log::add($message, Log::DEBUG, 'com_microschema');
            }
        : null;

        $container->set(
            ExistingSchemaChecker::class,
            static fn (): ExistingSchemaChecker => new ExistingSchemaChecker($debugLogger),
            true,
        );

        $container->set(
            ExistingSocialMetadataChecker::class,
            static fn (): ExistingSocialMetadataChecker => new ExistingSocialMetadataChecker($debugLogger),
            true,
        );

        $container->set(
            PluginInterface::class,
            static function (Container $container): PluginInterface {
                $dispatcher = $container->get(DispatcherInterface::class);
                $plugin = new Microschema(
                    (array) PluginHelper::getPlugin('system', 'microschema'),
                    $container->get('config'),
                    new SchemaResolver(),
                    new SchemaLanguageEnricher(),
                    new SchemaIdentityEnricher(),
                    new SchemaDateEnricher(),
                    new JsonLdRenderer(),
                    $container->get(ExistingSchemaChecker::class),
                    new SchemaMarkupInjector(),
                    new SocialMetadataRenderer(),
                    $container->get(ExistingSocialMetadataChecker::class),
                    new SocialMetadataInjector(),
                    $container->get(UserFactoryInterface::class),
                );
                $plugin->setDispatcher($dispatcher);
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
