<?php

namespace Joomla\Plugin\Microschema\Content\DataType;

use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Component\Microschema\Administrator\DataSource\ContextualDataValue;
use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceField;
use Joomla\Component\Microschema\Administrator\DataSource\DataTypeInterface;

final class ArticleDataType implements DataTypeInterface
{
    public function getName(): string
    {
        return 'JoomlaArticle';
    }

    public function getFields(mixed $value, DataContext $context): array
    {
        return [
            new DataSourceField('title', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_TITLE')),
            new DataSourceField('content', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_CONTENT')),
            new DataSourceField('introtext', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_INTROTEXT')),
            new DataSourceField('fulltext', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_FULLTEXT')),
            new DataSourceField('metadesc', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_METADESC')),
            new DataSourceField('created', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_CREATED'), 'DateTime'),
            new DataSourceField('publish_up', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_PUBLISH_UP'), 'DateTime'),
            new DataSourceField('modified', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_MODIFIED'), 'DateTime'),
            new DataSourceField('link', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_LINK'), 'URL'),
            new DataSourceField('author', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_AUTHOR'), 'JoomlaUser'),
            new DataSourceField('category', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_CATEGORY'), 'JoomlaCategory'),
            new DataSourceField('images', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_IMAGES'), 'JoomlaArticleImages'),
            new DataSourceField('fields', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_CUSTOM_FIELDS'), 'JoomlaCustomFields'),
            new DataSourceField('tags', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_TAGS'), 'List'),
            new DataSourceField('hits', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_HITS'), 'Integer'),
            new DataSourceField('alias', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_ALIAS')),
            new DataSourceField('id', Text::_('PLG_MICROSCHEMA_CONTENT_DATA_FIELD_ID'), 'Integer'),
        ];
    }

    public function resolve(mixed $value, string $field, DataContext $context): mixed
    {
        if (!$value instanceof ContextualDataValue) {
            return null;
        }

        return match ($field) {
            'content' => $this->content($value->value),
            'link' => $this->link($value->value),
            'author' => $this->author($value),
            'category' => $this->category($value),
            'fields' => $value,
            'images' => $this->read($value->value, 'images'),
            'tags' => $this->tags($value),
            default => $this->read($value->value, $field),
        };
    }

    /** @return list<string> */
    private function tags(ContextualDataValue $value): array
    {
        $article = $value->value;
        $id = (int) ($this->read($article, 'id') ?? $value->itemId);

        if ($id < 1) {
            return [];
        }

        $tags = $this->read($article, 'tags');
        $items = $tags instanceof TagsHelper ? $tags->itemTags : $tags;
        $titles = $this->tagTitles($items);

        if ($titles !== []) {
            return $titles;
        }

        return $this->tagTitles((new TagsHelper())->getItemTags('com_content.article', $id));
    }

    /** @return list<string> */
    private function tagTitles(mixed $items): array
    {
        if (!is_iterable($items)) {
            return [];
        }

        $titles = [];

        foreach ($items as $item) {
            if (!is_object($item) && !is_array($item)) {
                continue;
            }

            $title = trim((string) ($this->read($item, 'title') ?? ''));

            if ($title !== '') {
                $titles[] = $title;
            }
        }

        return array_values(array_unique($titles));
    }

    private function content(object|array $article): ?string
    {
        $intro = (string) ($this->read($article, 'introtext') ?? '');
        $full = (string) ($this->read($article, 'fulltext') ?? '');
        $value = trim($intro.($intro !== '' && $full !== '' ? ' ' : '').$full);

        return $value === '' ? null : $value;
    }

    private function link(object|array $article): ?string
    {
        $id = (int) ($this->read($article, 'id') ?? 0);

        if ($id < 1) {
            return null;
        }

        $alias = trim((string) ($this->read($article, 'alias') ?? ''));
        $category = (int) ($this->read($article, 'catid') ?? 0);
        $language = (string) ($this->read($article, 'language') ?? '*');
        $slug = $alias === '' ? (string) $id : $id.':'.$alias;

        return Route::_(RouteHelper::getArticleRoute($slug, $category, $language), true, 0, true);
    }

    private function author(ContextualDataValue $value): mixed
    {
        $article = $value->value;
        $id = (int) ($this->read($article, 'created_by') ?? 0);
        $alias = trim((string) ($this->read($article, 'created_by_alias') ?? ''));

        if ($id < 1) {
            return null;
        }

        return new ContextualDataValue(
            'com_users.user',
            $id,
            $id,
            overrides: $alias === '' ? [] : ['name' => $alias],
        );
    }

    private function category(ContextualDataValue $value): mixed
    {
        $article = $value->value;
        $id = (int) ($this->read($article, 'catid') ?? 0);

        if ($id < 1) {
            return null;
        }

        $category = Categories::getInstance('content', ['countItems' => true])->get($id);

        if (!is_object($category)) {
            return null;
        }

        $language = (string) ($this->read($category, 'language') ?? '*');

        return new ContextualDataValue(
            'com_content.categories',
            $id,
            $category,
            overrides: [
                'link' => Route::_(RouteHelper::getCategoryRoute($id, $language), true, 0, true),
            ],
        );
    }

    private function read(object|array $value, string $field): mixed
    {
        return is_array($value) ? ($value[$field] ?? null) : ($value->{$field} ?? null);
    }
}
