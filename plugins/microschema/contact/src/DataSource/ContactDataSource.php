<?php

namespace Joomla\Plugin\Microschema\Contact\DataSource;

use Joomla\CMS\Language\Text;
use Joomla\Component\Microschema\Administrator\DataSource\ContextualDataValue;
use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceInterface;

final class ContactDataSource implements DataSourceInterface
{
    private const CONTEXT = 'com_contact.contact';

    private const CATEGORY_TEMPLATE_CONTEXT = 'com_contact.category.contacts';

    public function getName(): string
    {
        return 'contact';
    }

    public function getLabel(): string
    {
        return Text::_('PLG_MICROSCHEMA_CONTACT_DATA_SOURCE_CONTACT');
    }

    public function supportsContext(string $context): bool
    {
        return in_array($context, [self::CONTEXT, self::CATEGORY_TEMPLATE_CONTEXT], true);
    }

    public function getType(): string
    {
        return 'JoomlaContact';
    }

    public function getValue(DataContext $context): mixed
    {
        if (!$this->supportsContext($context->context)) {
            return null;
        }

        if ($context->context === self::CATEGORY_TEMPLATE_CONTEXT) {
            return new ContextualDataValue(self::CONTEXT, 0, []);
        }

        return new ContextualDataValue(
            $context->context,
            $context->itemId,
            $context->item,
            $context->fieldValues,
        );
    }
}
