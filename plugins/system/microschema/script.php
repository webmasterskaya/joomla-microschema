<?php

/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') || exit;

use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            InstallerScriptInterface::class,
            new class($container->get(DatabaseInterface::class)) implements InstallerScriptInterface {
                public function __construct(private readonly DatabaseInterface $db)
                {
                }

                public function install(InstallerAdapter $adapter): bool
                {
                    $plugin = (object) [
                        'type' => 'plugin',
                        'element' => $adapter->getElement(),
                        'folder' => (string) $adapter->getParent()->manifest->attributes()['group'],
                        'enabled' => 1,
                    ];

                    $this->db->updateObject('#__extensions', $plugin, ['type', 'element', 'folder']);

                    return true;
                }

                public function update(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                public function uninstall(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                public function preflight(string $type, InstallerAdapter $adapter): bool
                {
                    return true;
                }

                public function postflight(string $type, InstallerAdapter $adapter): bool
                {
                    return true;
                }
            }
        );
    }
};
