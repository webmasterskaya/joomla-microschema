<?php

namespace Joomla\Plugin\System\Microschema\DataSource;

use Joomla\CMS\Language\Text;
use Joomla\Component\Microschema\Administrator\DataSource\ContextualDataValue;
use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceInterface;

final class CategoryDataSource implements DataSourceInterface
{
    public function getName(): string
    {
        return 'category';
    }

    public function getLabel(): string
    {
        return Text::_('PLG_SYSTEM_MICROSCHEMA_DATA_SOURCE_CATEGORY');
    }

    public function supportsContext(string $context): bool
    {
        return str_ends_with($context, '.categories');
    }

    public function getType(): string
    {
        return 'JoomlaCategory';
    }

    public function getValue(DataContext $context): mixed
    {
        if (!$this->supportsContext($context->context)) {
            return null;
        }

        if ($context->item instanceof ContextualDataValue) {
            return $context->item;
        }

        return new ContextualDataValue(
            $context->context,
            $context->itemId,
            $context->item,
            $context->fieldValues,
        );
    }
}
