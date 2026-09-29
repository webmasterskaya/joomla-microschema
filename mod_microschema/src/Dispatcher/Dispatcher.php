<?php

namespace Joomla\Module\Microschema\Site\Dispatcher;

defined('_JEXEC') || exit;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

final class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();

        $this->getHelperFactory()
            ->getHelper('MicroschemaHelper')
            ->registerSchema($data['params'], $data['module'], $this->getApplication());

        return $data;
    }
}
