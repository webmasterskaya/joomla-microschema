<?php

declare(strict_types=1);

namespace Joomla\CMS\Language {
    final class Text
    {
        public static function _(string $key): string
        {
            return $key;
        }
    }
}

namespace {
    use Joomla\Component\Microschema\Administrator\DataSource\ContextualDataValue;
    use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
    use Joomla\Plugin\System\Microschema\DataSource\CategoryDataSource;
    use Joomla\Plugin\System\Microschema\DataType\JoomlaCategoryDataType;

    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/ContextualDataValue.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataContext.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataSourceField.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataSourceInterface.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataTypeInterface.php';
    require_once __DIR__ . '/../../../plugins/system/microschema/src/DataSource/CategoryDataSource.php';
    require_once __DIR__ . '/../../../plugins/system/microschema/src/DataType/JoomlaCategoryDataType.php';

    function assertCategoryTypeSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message);
        }
    }

    $parent = (object) ['id' => 2, 'title' => 'Parent'];
    $item = new class ($parent) {
        public int $id = 7;
        public string $title = 'Category';

        public function __construct(private readonly object $parent)
        {
        }

        public function getParent(): object
        {
            return $this->parent;
        }
    };
    $value   = new ContextualDataValue('com_content.categories', 7, $item, overrides: ['link' => '/category']);
    $context = new DataContext('com_content.article', 42, []);
    $type    = new JoomlaCategoryDataType();
    $fields  = $type->getFields($value, $context);
    $source  = new CategoryDataSource();

    assertCategoryTypeSame('JoomlaCategory', $type->getName(), 'The category type must be integration-neutral.');
    assertCategoryTypeSame('Category', $type->resolve($value, 'title', $context), 'The category must resolve its own fields.');
    assertCategoryTypeSame('/category', $type->resolve($value, 'link', $context), 'Integrations may provide a category route override.');
    assertCategoryTypeSame('JoomlaCategory', $fields[6]->type ?? null, 'Parent categories must reference the same reusable type.');
    assertCategoryTypeSame('JoomlaCustomFields', $fields[7]->type ?? null, 'Categories must expose contextual custom fields.');

    $resolvedParent = $type->resolve($value, 'parent', $context);
    assertCategoryTypeSame(ContextualDataValue::class, $resolvedParent::class, 'Parent categories must remain contextual values.');
    assertCategoryTypeSame('Parent', $type->resolve($resolvedParent, 'title', $context), 'Parent categories must resolve independently.');
    assertCategoryTypeSame($value, $type->resolve($value, 'fields', $context), 'Custom fields must receive the category owner.');
    assertCategoryTypeSame('category', $source->getName(), 'The universal category source name must be stable.');
    assertCategoryTypeSame(true, $source->supportsContext('com_content.categories'), 'Content categories must be supported.');
    assertCategoryTypeSame(true, $source->supportsContext('com_contact.categories'), 'Contact categories must be supported.');
    assertCategoryTypeSame(false, $source->supportsContext('com_content.article'), 'Non-category contexts must be rejected.');
    assertCategoryTypeSame($value, $source->getValue(new DataContext('com_content.categories', 7, $value)), 'Existing contextual category values must retain their overrides.');

    echo "Joomla category data type tests passed.\n";
}
