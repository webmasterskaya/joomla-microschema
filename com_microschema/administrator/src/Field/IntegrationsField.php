<?php

namespace Joomla\Component\Microschema\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Microschema\Administrator\Extension\MicroschemaComponent;

final class IntegrationsField extends FormField
{
    protected $type = 'Integrations';

    protected function getInput(): string
    {
        $application = Factory::getApplication();
        $component = $application->bootComponent('com_microschema');

        if (!$component instanceof MicroschemaComponent) {
            throw new \RuntimeException('The com_microschema component is not available.');
        }

        $repository = $component->getIntegrationPluginRepository();
        $language = $application->getLanguage();
        $integrations = array_map(
            static function (array $integration) use ($language): array {
                $language->load($integration['name'], JPATH_ADMINISTRATOR);
                $languageKey = strtoupper($integration['name']);
                $label = Text::_($languageKey);

                if ($label === $languageKey) {
                    $label = ucfirst($integration['element']);
                }

                return $integration + [
                    'label' => $label,
                    'url' => Route::_(
                        'index.php?option=com_plugins&task=plugin.edit&extension_id='.$integration['id'],
                        false,
                    ),
                ];
            },
            $repository->getAll(),
        );
        $identity = $application->getIdentity();
        $canConfigure = $identity !== null
            && $identity->authorise('core.manage', 'com_plugins')
            && $identity->authorise('core.edit', 'com_plugins');

        return LayoutHelper::render(
            'field.integrations',
            [
                'integrations' => $integrations,
                'canConfigure' => $canConfigure,
            ],
            dirname(__DIR__, 2).'/layouts',
        );
    }
}
