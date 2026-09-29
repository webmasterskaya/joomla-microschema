<?php

namespace Joomla\Plugin\Microschema\Content\DataSource;

use Joomla\CMS\Language\Text;
use Joomla\Component\Microschema\Administrator\DataSource\ContextualDataValue;
use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceInterface;

final class ArticleDataSource implements DataSourceInterface
{
    private const CONTEXT = 'com_content.article';

    private const CATEGORY_TEMPLATE_CONTEXT = 'com_content.category.articles';

    public function getName(): string
    {
        return 'article';
    }

    public function getLabel(): string
    {
        return Text::_('PLG_MICROSCHEMA_CONTENT_DATA_SOURCE_ARTICLE');
    }

    public function supportsContext(string $context): bool
    {
        return in_array($context, [self::CONTEXT, self::CATEGORY_TEMPLATE_CONTEXT], true);
    }

    public function getType(): string
    {
        return 'JoomlaArticle';
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
