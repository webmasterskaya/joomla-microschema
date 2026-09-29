<?php

namespace Joomla\Component\Microschema\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;

final class ConfigBehaviorField extends FormField
{
    protected $type = 'ConfigBehavior';

    protected function getInput(): string
    {
        $assets = Factory::getApplication()
            ->getDocument()
            ->getWebAssetManager();

        $assets->getRegistry()->addExtensionRegistryFile('com_microschema');
        $assets->useScript('com_microschema.config');

        return '';
    }
}
