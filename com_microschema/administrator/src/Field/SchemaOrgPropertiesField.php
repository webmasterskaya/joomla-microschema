<?php

namespace Joomla\Component\Microschema\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
use Joomla\Component\Microschema\Administrator\Extension\MicroschemaComponent;
use Joomla\Component\Microschema\Administrator\Form\SchemaOrgFormDefinitionBuilder;

final class SchemaOrgPropertiesField extends FormField
{
    protected $type = 'SchemaOrgProperties';

    protected function getInput(): string
    {
        $app = Factory::getApplication();
        $component = $app->bootComponent('com_microschema');

        if (!$component instanceof MicroschemaComponent) {
            throw new \RuntimeException('The com_microschema component is not available.');
        }

        $fixedSchemaType = trim((string) ($this->element['schematype'] ?? ''));
        $schemaField = trim((string) ($this->element['schemafield'] ?? 'schema_type'));
        $schemaTypeField = null;

        if ($fixedSchemaType === '') {
            $schemaTypeField = $this->form->getField($schemaField, $this->group);

            if (!$schemaTypeField instanceof FormField) {
                throw new \RuntimeException(sprintf('The "%s" Schema.org type field is unavailable.', $schemaField));
            }
        }

        $document = $app->getDocument();
        $assets = $document->getWebAssetManager();
        $assets->getRegistry()->addExtensionRegistryFile('com_microschema');
        $assets->useStyle('com_microschema.schema-editor');
        $assets->useScript('com_microschema.schema-editor');

        $app->getLanguage()->load('com_microschema', JPATH_ADMINISTRATOR);

        $language = $app->getLanguage();
        $calendar = strtolower($language->getCalendar() ?: 'gregorian');
        $helperPath = 'system/fields/calendar-locales/date/gregorian/date-helper.min.js';

        if (is_dir(JPATH_ROOT.'/media/system/js/fields/calendar-locales/date/'.$calendar)) {
            $helperPath = 'system/fields/calendar-locales/date/'.$calendar.'/date-helper.min.js';
        }

        $assets
            ->registerAndUseScript('field.calendar.helper', $helperPath, [], ['defer' => true])
            ->useStyle('field.calendar')
            ->useScript('field.calendar');

        foreach ($this->getCalendarLanguageKeys() as $key) {
            Text::script($key);
        }

        $schemas = $component->getMetadataRegistry()->getSchemaOrg();

        if ($fixedSchemaType !== '' && !isset($schemas[$fixedSchemaType])) {
            throw new \RuntimeException(sprintf('The "%s" Schema.org type is unavailable.', $fixedSchemaType));
        }

        $schemaType = $fixedSchemaType !== ''
            ? $fixedSchemaType
            : trim((string) $this->form->getValue($schemaField, $this->group));
        $context = trim((string) ($this->element['context'] ?? ''));
        $excludedSources = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) ($this->element['excludesources'] ?? '')),
        )));
        $formData = $this->form->getData();

        if (!is_array($formData) && !is_object($formData)) {
            $formData = [];
        }

        if (is_array($formData)) {
            $fieldValues = $formData['com_fields'] ?? [];
        } elseif (method_exists($formData, 'get')) {
            $fieldValues = $formData->get('com_fields', []);
        } else {
            $fieldValues = $formData->com_fields ?? [];
        }

        $dataContext = new DataContext(
            context: $context,
            itemId: (int) $this->form->getValue('id'),
            item: $formData,
            fieldValues: is_array($fieldValues) ? $fieldValues : [],
        );
        $definition = (new SchemaOrgFormDefinitionBuilder($schemas))->build($this->value);
        $instanceId = $this->id.'-'.substr(hash('sha256', $this->name), 0, 8);
        $definition = [
            'instanceId' => $instanceId,
            'fieldName' => $this->name,
            'schemaTypeField' => $schemaTypeField?->id ?? '',
            'schemaType' => $schemaType,
            'dataSourceCatalog' => $context === ''
                ? ['sources' => [], 'collections' => []]
                : $component->getDataSourceTemplateResolver()->getCatalog($dataContext, $excludedSources),
            'calendar' => [
                'calendarType' => $calendar,
                'dateFormat' => '%Y-%m-%d',
                'dateTimeFormat' => '%Y-%m-%d %H:%M:%S',
                'fillTable' => 1,
                'firstDay' => $language->getFirstDay(),
                'openLabel' => Text::_('JLIB_HTML_BEHAVIOR_OPEN_CALENDAR'),
                'singleHeader' => 0,
                'timeFormat' => 24,
                'todayButton' => 1,
                'weekNumbers' => 1,
                'weekend' => explode(',', $language->getWeekEnd()),
            ],
            'labels' => [
                'addItem' => Text::_('COM_MICROSCHEMA_SCHEMA_EDITOR_ADD_ITEM'),
                'addCollection' => Text::_('COM_MICROSCHEMA_SCHEMA_EDITOR_ADD_COLLECTION'),
                'addProperty' => Text::_('COM_MICROSCHEMA_SCHEMA_EDITOR_ADD_PROPERTY'),
                'back' => Text::_('COM_MICROSCHEMA_SCHEMA_EDITOR_BACK'),
                'close' => Text::_('JCLOSE'),
                'dynamicValues' => Text::_('COM_MICROSCHEMA_SCHEMA_EDITOR_DYNAMIC_VALUES'),
                'collection' => Text::_('COM_MICROSCHEMA_SCHEMA_EDITOR_COLLECTION'),
                'collectionItem' => Text::_('COM_MICROSCHEMA_SCHEMA_EDITOR_COLLECTION_ITEM'),
                'empty' => Text::_('COM_MICROSCHEMA_SCHEMA_EDITOR_EMPTY'),
                'edit' => Text::_('COM_MICROSCHEMA_SCHEMA_EDITOR_EDIT'),
                'insertDataSource' => Text::_('COM_MICROSCHEMA_SCHEMA_EDITOR_INSERT_DATA_SOURCE'),
                'removeItem' => Text::_('COM_MICROSCHEMA_SCHEMA_EDITOR_REMOVE_ITEM'),
                'removeProperty' => Text::_('COM_MICROSCHEMA_SCHEMA_EDITOR_REMOVE_PROPERTY'),
                'selectProperty' => Text::_('COM_MICROSCHEMA_SCHEMA_EDITOR_SELECT_PROPERTY'),
                'selectDataSource' => Text::_('COM_MICROSCHEMA_SCHEMA_EDITOR_SELECT_DATA_SOURCE'),
                'type' => Text::_('COM_MICROSCHEMA_SCHEMA_EDITOR_TYPE'),
                'unknownDataSource' => Text::_('COM_MICROSCHEMA_SCHEMA_EDITOR_UNKNOWN_DATA_SOURCE'),
                'yes' => Text::_('JYES'),
                'no' => Text::_('JNO'),
            ],
        ] + $definition;

        $document->addScriptOptions(
            'com_microschema.schemaEditors',
            [$instanceId => $definition],
            true,
        );

        return sprintf(
            '<div id="%s" class="microschema-schema-editor" data-microschema-schema-editor></div>',
            htmlspecialchars($instanceId, ENT_QUOTES, 'UTF-8'),
        );
    }

    /** @return list<string> */
    private function getCalendarLanguageKeys(): array
    {
        return [
            'SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY',
            'SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT',
            'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
            'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER',
            'JANUARY_SHORT', 'FEBRUARY_SHORT', 'MARCH_SHORT', 'APRIL_SHORT', 'MAY_SHORT', 'JUNE_SHORT',
            'JULY_SHORT', 'AUGUST_SHORT', 'SEPTEMBER_SHORT', 'OCTOBER_SHORT', 'NOVEMBER_SHORT', 'DECEMBER_SHORT',
            'JCLOSE', 'JCLEAR', 'JLIB_HTML_BEHAVIOR_TODAY', 'JLIB_HTML_BEHAVIOR_WK',
            'JLIB_HTML_BEHAVIOR_AM', 'JLIB_HTML_BEHAVIOR_PM',
        ];
    }
}
