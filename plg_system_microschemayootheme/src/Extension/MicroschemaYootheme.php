<?php

/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\MicroschemaYootheme\Extension;

defined('_JEXEC') || exit;

use Joomla\CMS\Event\Application\AfterInitialiseEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use YOOtheme\Application;

final class MicroschemaYootheme extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterInitialise' => 'onAfterInitialise',
        ];
    }

    public function onAfterInitialise(AfterInitialiseEvent $event): void
    {
        if (!class_exists(Application::class)) {
            return;
        }

        $this->loadLanguage();
        Application::getInstance()->load(dirname(__DIR__, 2).'/bootstrap.php');
    }
}
