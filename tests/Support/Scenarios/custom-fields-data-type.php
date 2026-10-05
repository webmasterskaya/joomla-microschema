<?php

declare(strict_types=1);

namespace Joomla\Component\Fields\Administrator\Helper {
    final class FieldsHelper
    {
        /** @var array<string, array<int, object>> */
        public static array $fields = [];

        public static function getFields(
            string $context,
            mixed $item,
            bool $prepareValue = false,
            ?array $values = null,
        ): array {
            return self::$fields[$context] ?? [];
        }
    }
}

namespace {
    use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
    use Joomla\Component\Microschema\Administrator\DataSource\ContextualDataValue;
    use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
    use Joomla\Plugin\System\Microschema\DataType\CustomFieldsDataType;

    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/ContextualDataValue.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataContext.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataSourceField.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataTypeInterface.php';
    require_once __DIR__ . '/../../../plugins/system/microschema/src/DataType/CustomFieldsDataType.php';

    function assertCustomFieldsSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message);
        }
    }

    FieldsHelper::$fields = [
        'com_content.article' => [
            (object) ['name' => 'excerpt', 'title' => 'Excerpt', 'type' => 'text', 'rawvalue' => 'Article excerpt'],
        ],
        'com_content.categories' => [
            (object) ['name' => 'subtitle', 'title' => 'Subtitle', 'type' => 'text', 'rawvalue' => 'Category subtitle'],
        ],
    ];

    $context  = new DataContext('com_content.article', 42, []);
    $article  = new ContextualDataValue('com_content.article', 42, ['id' => 42]);
    $category = new ContextualDataValue('com_content.categories', 7, ['id' => 7]);
    $type     = new CustomFieldsDataType();

    assertCustomFieldsSame('excerpt', $type->getFields($article, $context)[0]->name ?? null, 'Article fields must use the article context.');
    assertCustomFieldsSame('subtitle', $type->getFields($category, $context)[0]->name ?? null, 'Category fields must use the category context.');
    assertCustomFieldsSame('Article excerpt', $type->resolve($article, 'excerpt', $context), 'Article field values must resolve independently.');
    assertCustomFieldsSame('Category subtitle', $type->resolve($category, 'subtitle', $context), 'Category field values must resolve independently.');
    assertCustomFieldsSame(null, $type->resolve($article, 'subtitle', $context), 'Fields from another owner context must be rejected.');

    echo "Custom fields data type tests passed.\n";
}
