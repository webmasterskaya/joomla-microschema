<?php

namespace Joomla\Plugin\Microschema\Content\DataType;

use Joomla\CMS\Language\Text;
use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceField;
use Joomla\Component\Microschema\Administrator\DataSource\DataTypeInterface;
use Joomla\Registry\Registry;

final class ArticleImagesDataType implements DataTypeInterface
{
    public function getName(): string
    {
        return 'JoomlaArticleImages';
    }

    public function getFields(mixed $value, DataContext $context): array
    {
        return [
            new DataSourceField('image_intro', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_IMAGE_INTRO'), 'URL'),
            new DataSourceField('image_intro_alt', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_IMAGE_INTRO_ALT')),
            new DataSourceField('image_intro_caption', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_IMAGE_INTRO_CAPTION')),
            new DataSourceField('image_fulltext', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_IMAGE_FULLTEXT'), 'URL'),
            new DataSourceField('image_fulltext_alt', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_IMAGE_FULLTEXT_ALT')),
            new DataSourceField('image_fulltext_caption', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_IMAGE_FULLTEXT_CAPTION')),
        ];
    }

    public function resolve(mixed $value, string $field, DataContext $context): mixed
    {
        $values = $this->normalise($value);

        return is_array($values) ? ($values[$field] ?? null) : null;
    }

    private function normalise(mixed $value): mixed
    {
        if ($value instanceof Registry) {
            return $value->toArray();
        }

        if (is_object($value)) {
            return get_object_vars($value);
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : null;
        }

        return $value;
    }
}
