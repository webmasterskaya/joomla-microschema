<?php

/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') || exit;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\System\MicroschemaYootheme\Extension\MicroschemaYootheme;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            static function (Container $container): PluginInterface {
                $plugin = new MicroschemaYootheme(
                    (array) PluginHelper::getPlugin('system', 'microschemayootheme'),
                );
                $plugin->setApplication(Factory::getApplication());
                $plugin->setDispatcher($container->get(DispatcherInterface::class));

                return $plugin;
            }
        );
    }
};
