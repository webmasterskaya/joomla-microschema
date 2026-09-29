<?php

/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') || exit;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Microschema\Administrator\Model\ItemModel;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Microschema\Content\Extension\Content;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('\\Joomla\\Component\\Microschema'));

        $container->set(
            PluginInterface::class,
            static function (Container $container): PluginInterface {
                $itemModel = $container->get(MVCFactoryInterface::class)->createModel(
                    'Item',
                    'Administrator',
                    ['ignore_request' => true],
                );

                if (!$itemModel instanceof ItemModel) {
                    throw new RuntimeException('The MicroSchema item model is not available.');
                }

                $application = Factory::getApplication();
                $currentUser = $application->getIdentity();

                if ($currentUser !== null) {
                    $itemModel->setCurrentUser($currentUser);
                }

                $plugin = new Content(
                    (array) PluginHelper::getPlugin('microschema', 'content'),
                    $itemModel,
                );

                $plugin->setApplication($application);
                $plugin->setDispatcher($container->get(DispatcherInterface::class));

                return $plugin;
            }
        );
    }
};
