<?php

namespace Joomla\Plugin\Microschema\Contact\DataType;

use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Contact\Site\Helper\RouteHelper;
use Joomla\Component\Microschema\Administrator\DataSource\ContextualDataValue;
use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceField;
use Joomla\Component\Microschema\Administrator\DataSource\DataTypeInterface;
use Joomla\Registry\Registry;

final class ContactDataType implements DataTypeInterface
{
    public function getName(): string
    {
        return 'JoomlaContact';
    }

    public function getFields(mixed $value, DataContext $context): array
    {
        return [
            new DataSourceField('name', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_NAME')),
            new DataSourceField('image', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_IMAGE')),
            new DataSourceField('email_to', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_EMAIL')),
            new DataSourceField('con_position', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_POSITION')),
            new DataSourceField('address', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_ADDRESS')),
            new DataSourceField('suburb', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_SUBURB')),
            new DataSourceField('state', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_STATE')),
            new DataSourceField('postcode', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_POSTCODE')),
            new DataSourceField('country', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_COUNTRY')),
            new DataSourceField('telephone', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_TELEPHONE')),
            new DataSourceField('mobile', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_MOBILE')),
            new DataSourceField('fax', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_FAX')),
            new DataSourceField('webpage', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_WEBPAGE'), 'URL'),
            new DataSourceField('misc', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_MISC')),
            new DataSourceField('metadesc', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_METADESC')),
            new DataSourceField('created', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_CREATED'), 'DateTime'),
            new DataSourceField('modified', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_MODIFIED'), 'DateTime'),
            new DataSourceField('link', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_LINK'), 'URL'),
            new DataSourceField('user', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_USER'), 'JoomlaUser'),
            new DataSourceField('category', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_CATEGORY'), 'JoomlaCategory'),
            new DataSourceField('fields', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_CUSTOM_FIELDS'), 'JoomlaCustomFields'),
            new DataSourceField('hits', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_HITS'), 'Integer'),
            new DataSourceField('alias', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_ALIAS')),
            new DataSourceField('id', Text::_('PLG_MICROSCHEMA_CONTACT_DATA_FIELD_ID'), 'Integer'),
        ];
    }

    public function resolve(mixed $value, string $field, DataContext $context): mixed
    {
        if (!$value instanceof ContextualDataValue) {
            return null;
        }

        return match ($field) {
            'link' => $this->link($value->value),
            'user' => $this->user($value->value),
            'category' => $this->category($value->value),
            'fields' => $value,
            default => $this->read($value->value, $field),
        };
    }

    private function link(object|array $contact): ?string
    {
        $id = (int) ($this->read($contact, 'id') ?? 0);

        if ($id < 1) {
            return null;
        }

        $alias = trim((string) ($this->read($contact, 'alias') ?? ''));
        $category = (int) ($this->read($contact, 'catid') ?? 0);
        $language = (string) ($this->read($contact, 'language') ?? '*');
        $slug = $alias === '' ? (string) $id : $id.':'.$alias;

        return Route::_(RouteHelper::getContactRoute($slug, $category, $language), true, 0, true);
    }

    private function user(object|array $contact): ?ContextualDataValue
    {
        $id = (int) ($this->read($contact, 'user_id') ?? 0);

        return $id > 0
            ? new ContextualDataValue('com_users.user', $id, $id)
            : null;
    }

    private function category(object|array $contact): ?ContextualDataValue
    {
        $id = (int) ($this->read($contact, 'catid') ?? 0);

        if ($id < 1) {
            return null;
        }

        $category = Categories::getInstance('contact', ['countItems' => true])->get($id);

        if (!is_object($category)) {
            return null;
        }

        $language = (string) ($this->read($category, 'language') ?? '*');

        return new ContextualDataValue(
            'com_contact.categories',
            $id,
            $category,
            overrides: [
                'link' => Route::_(RouteHelper::getCategoryRoute($id, $language), true, 0, true),
            ],
        );
    }

    private function read(object|array $value, string $field): mixed
    {
        if ($value instanceof Registry) {
            return $value->get($field);
        }

        return is_array($value) ? ($value[$field] ?? null) : ($value->{$field} ?? null);
    }
}
