<?php

declare(strict_types=1);

namespace Joomla\CMS\Application {
    interface CMSApplicationInterface
    {
        public function isClient($identifier);
    }
}

namespace Joomla\CMS\Categories {
    final class Categories
    {
        public static ?object $category = null;

        /** @var array<int, object> */
        public static array $items = [];

        public static function getInstance(string $extension, array $options = []): object
        {
            return new class () {
                public function get(int $id): object
                {
                    return Categories::$items[$id]
                        ?? Categories::$category
                        ?? (object) ['id' => 1];
                }
            };
        }
    }
}

namespace Joomla\CMS\Event\Model {
    use Joomla\CMS\Form\Form;

    class PrepareDataEvent
    {
        public function __construct(
            private readonly string $context,
            private object|array $data,
        ) {
        }

        public function getContext(): string
        {
            return $this->context;
        }

        public function getData(): object|array
        {
            return $this->data;
        }

        public function updateData(object|array $data): static
        {
            $this->data = $data;

            return $this;
        }
    }

    class AfterSaveEvent
    {
        public function __construct(
            private readonly string $context,
            private readonly object $item,
            private readonly object|array|null $data,
        ) {
        }

        public function getContext(): string
        {
            return $this->context;
        }

        public function getItem(): object
        {
            return $this->item;
        }

        public function getData(): object|array|null
        {
            return $this->data;
        }
    }

    class AfterDeleteEvent
    {
        public function __construct(
            private readonly string $context,
            private readonly object $item,
        ) {
        }

        public function getContext(): string
        {
            return $this->context;
        }

        public function getItem(): object
        {
            return $this->item;
        }
    }

    class PrepareFormEvent
    {
        public function __construct(
            private readonly Form $form,
            private readonly object|array|null $data = null,
        )
        {
        }

        public function getForm(): Form
        {
            return $this->form;
        }

        public function getData(): object|array
        {
            return $this->data ?? $this->form->getData();
        }
    }
}

namespace Joomla\CMS\Language {
    class Text
    {
        public static function _(string $key): string
        {
            return $key;
        }
    }
}

namespace Joomla\CMS\Router {
    final class Route
    {
        public static function _(string $url, bool $xhtml = true, int $tls = 0, bool $absolute = false): string
        {
            return $absolute ? 'https://example.test/' . ltrim($url, '/') : $url;
        }
    }
}

namespace Joomla\Component\Content\Site\Helper {
    final class RouteHelper
    {
        public static function getCategoryRoute(int $id, string $language = '*'): string
        {
            return 'content-category/' . $id;
        }
    }
}

namespace Joomla\CMS\Form {
    class Form
    {
        public ?string $loadedFile = null;

        public function __construct(
            private readonly string $name,
            private readonly object|array $data = [],
        ) {
        }

        public function getName(): string
        {
            return $this->name;
        }

        public function getData(): object|array
        {
            return $this->data;
        }

        public function loadFile(string $file): bool
        {
            $this->loadedFile = $file;

            return is_file($file);
        }
    }
}

namespace Joomla\CMS\Plugin {
    use Joomla\CMS\Application\CMSApplicationInterface;
    use Joomla\Registry\Registry;

    class CMSPlugin
    {
        private CMSApplicationInterface $application;

        protected Registry $params;

        public function __construct(array $config = [])
        {
            $params       = $config['params'] ?? [];
            $this->params = $params instanceof Registry
                ? $params
                : new Registry(is_array($params) ? $params : []);
        }

        public function setApplication(CMSApplicationInterface $application): void
        {
            $this->application = $application;
        }

        public function getApplication(): CMSApplicationInterface
        {
            return $this->application;
        }
    }
}

namespace Joomla\Component\Microschema\Administrator\Model {
    class ItemModel
    {
        /** @var array<string, mixed> */
        public array $storedParams = [];

        /** @var array<string, array<string, mixed>> */
        public array $storedParamsByContext = [];

        /** @var list<array{context: string, itemId: int, params: array<string, mixed>}> */
        public array $saved = [];

        /** @var list<array{context: string, itemId: int}> */
        public array $deleted = [];

        /** @return array<string, mixed> */
        public function getParamsByContext(string $context, int $itemId): array
        {
            return $this->storedParamsByContext[$context . ':' . $itemId] ?? $this->storedParams;
        }

        /** @param array<string, mixed> $params */
        public function saveParamsByContext(string $context, int $itemId, array $params): bool
        {
            $this->saved[] = compact('context', 'itemId', 'params');

            return true;
        }

        public function deleteItemByContext(string $context, int $itemId): bool
        {
            $this->deleted[] = compact('context', 'itemId');

            return true;
        }
    }
}

namespace Joomla\Event {
    interface DispatcherInterface
    {
    }

    interface DispatcherAwareInterface
    {
        public function setDispatcher(DispatcherInterface $dispatcher): void;
    }

    trait DispatcherAwareTrait
    {
        private DispatcherInterface $dispatcher;

        public function setDispatcher(DispatcherInterface $dispatcher): void
        {
            $this->dispatcher = $dispatcher;
        }
    }

    interface SubscriberInterface
    {
        public static function getSubscribedEvents(): array;
    }
}

namespace Joomla\CMS\Extension {
    use Joomla\Event\DispatcherAwareInterface;

    interface PluginInterface extends DispatcherAwareInterface
    {
        public function registerListeners(): void;
    }
}

namespace Joomla\Registry {
    class Registry
    {
        public int $magicReads = 0;

        public function __construct(private readonly array $data = [])
        {
        }

        public function toArray(): array
        {
            return $this->data;
        }

        public function get(string $key, mixed $default = null): mixed
        {
            return $this->data[$key] ?? $default;
        }

        public function __get(string $key): mixed
        {
            ++$this->magicReads;

            return $this->data[$key] ?? null;
        }
    }
}

namespace Joomla\Component\Microschema\Administrator\DataSource {
    interface DataSourceInterface
    {
        public function getName(): string;

        public function getLabel(): string;

        public function supportsContext(string $context): bool;

        public function getType(): string;

        public function getValue(DataContext $context): mixed;
    }

    interface DataTypeInterface
    {
        public function getName(): string;

        public function getFields(mixed $value, DataContext $context): array;

        public function resolve(mixed $value, string $field, DataContext $context): mixed;
    }

    final readonly class DataContext
    {
        public function __construct(
            public string $context,
            public int $itemId,
            public object|array $item,
            public array $fieldValues = [],
        ) {
        }
    }

    final readonly class ContextualDataValue
    {
        public function __construct(
            public string $context,
            public int $itemId,
            public mixed $value,
            public array $fieldValues = [],
            public array $overrides = [],
        ) {
        }
    }

    final readonly class DataSourceField
    {
        public function __construct(
            public string $name,
            public string $label,
            public string $type = 'String',
        ) {
        }
    }
}

namespace Joomla\Component\Microschema\Administrator\Event {
    use Joomla\Component\Microschema\Administrator\DataCollection\DataCollectionInterface;
    use Joomla\Component\Microschema\Administrator\DataSource\DataSourceInterface;
    use Joomla\Component\Microschema\Administrator\DataSource\DataTypeInterface;

    class RegisterDataSourcesEvent
    {
        public const NAME = 'onMicroschemaRegisterDataSources';

        /** @var list<DataSourceInterface> */
        public array $sources = [];

        public function register(DataSourceInterface $source): void
        {
            $this->sources[] = $source;
        }
    }

    class RegisterDataCollectionsEvent
    {
        public const NAME = 'onMicroschemaRegisterDataCollections';

        /** @var list<DataCollectionInterface> */
        public array $collections = [];

        public function register(DataCollectionInterface $collection): void
        {
            $this->collections[] = $collection;
        }
    }

    class RegisterDataTypesEvent
    {
        public const NAME = 'onMicroschemaRegisterDataTypes';

        /** @var list<DataTypeInterface> */
        public array $types = [];

        public function register(DataTypeInterface $type): void
        {
            $this->types[] = $type;
        }
    }

    class CollectSchemasEvent
    {
        public const NAME = 'onMicroschemaCollectSchemas';

        /** @var list<array{context: string, id: int}> */
        public array $contexts = [];

        /** @var list<array{contextKey: string, uid: string, data: array<string, mixed>}> */
        public array $schemas = [];

        private object $path;

        public function __construct()
        {
            $this->path = new class () {
                /** @var array<string, true> */
                public array $keys = [];

                public function has(string $key): bool
                {
                    return isset($this->keys[$key]);
                }
            };
        }

        public function getPath(): object
        {
            return $this->path;
        }

        public function appendContext(string $context, int $id): object
        {
            $this->contexts[] = compact('context', 'id');
            $this->path->keys[$context . ':' . $id] = true;

            return (object) compact('context', 'id');
        }

        public function addMenuOverride(string $targetKey, int $menuItemId): object
        {
            $context = 'com_menus.item';
            $id      = $menuItemId;
            $key     = $context . ':' . $id;
            $this->contexts[] = compact('context', 'id');
            $this->path->keys[$key] = true;

            return new class ($key) {
                public function __construct(private readonly string $key)
                {
                }

                public function getKey(): string
                {
                    return $this->key;
                }
            };
        }

        /** @param array<string, mixed> $data */
        public function addSchema(string $contextKey, string $uid, array $data): object
        {
            $this->schemas[] = compact('contextKey', 'uid', 'data');

            return (object) compact('contextKey', 'uid', 'data');
        }
    }
}

namespace Joomla\Component\Microschema\Administrator\Extension {
    class MicroschemaComponent
    {
        public function getDataSourceTemplateResolver(): object
        {
            return new class () {
                public function resolve(mixed $value, object $context): mixed
                {
                    if (is_string($value)) {
                        $item = $context->item instanceof \Joomla\Component\Microschema\Administrator\DataSource\ContextualDataValue
                            ? $context->item->value
                            : $context->item;
                        $title = is_array($item) ? ($item['title'] ?? '') : ($item->title ?? '');
                        $name  = is_array($item) ? ($item['name'] ?? $title) : ($item->name ?? $title);

                        return str_replace(
                            ['{article.title}', '{contact.name}', '{category.title}'],
                            [(string) $title, (string) $name, (string) $title],
                            $value,
                        );
                    }

                    if (!is_array($value)) {
                        return $value;
                    }

                    return array_map(fn (mixed $item): mixed => $this->resolve($item, $context), $value);
                }
            };
        }

        public function getMetadataRegistry(): object
        {
            return new class () {
                public function getSchemaOrg(): array
                {
                    return [];
                }
            };
        }
    }
}

namespace Joomla\Component\Microschema\Administrator\Schema {
    class SchemaDataBuilder
    {
        public function __construct(array $objectTypes)
        {
        }

        public function build(string $schemaType, mixed $properties): array
        {
            return ['@type' => $schemaType] + (is_array($properties) ? $properties : []);
        }
    }
}

namespace {
    use Joomla\CMS\Application\CMSApplicationInterface;
    use Joomla\CMS\Categories\Categories;
    use Joomla\CMS\Event\Model\AfterDeleteEvent;
    use Joomla\CMS\Event\Model\AfterSaveEvent;
    use Joomla\CMS\Event\Model\PrepareDataEvent;
    use Joomla\CMS\Event\Model\PrepareFormEvent;
    use Joomla\CMS\Form\Form;
    use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
    use Joomla\Component\Microschema\Administrator\DataSource\ContextualDataValue;
    use Joomla\Component\Microschema\Administrator\Event\RegisterDataCollectionsEvent;
    use Joomla\Component\Microschema\Administrator\Event\RegisterDataSourcesEvent;
    use Joomla\Component\Microschema\Administrator\Event\RegisterDataTypesEvent;
    use Joomla\Component\Microschema\Administrator\Event\CollectSchemasEvent;
    use Joomla\Component\Microschema\Administrator\Extension\MicroschemaComponent;
    use Joomla\Component\Microschema\Administrator\Model\ItemModel;
    use Joomla\Plugin\Microschema\Content\DataSource\ArticleDataSource;
    use Joomla\Plugin\Microschema\Content\DataCollection\CategoryArticlesCollection;
    use Joomla\Plugin\Microschema\Content\DataCollection\MenuArticlesCollection;
    use Joomla\Plugin\Microschema\Content\DataCollection\MenuCategoriesCollection;
    use Joomla\Plugin\Microschema\Content\DataType\ArticleDataType;
    use Joomla\Plugin\Microschema\Content\DataType\ArticleImagesDataType;
    use Joomla\Plugin\Microschema\Content\Extension\Content;
    use Joomla\Registry\Registry;

    define('_JEXEC', 1);

    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataCollection/DataCollectionInterface.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataCollection/DataCollectionResult.php';
    require_once __DIR__ . '/../../../plugins/microschema/content/src/DataCollection/CategoryArticlesCollection.php';
    require_once __DIR__ . '/../../../plugins/microschema/content/src/DataCollection/MenuArticlesCollection.php';
    require_once __DIR__ . '/../../../plugins/microschema/content/src/DataCollection/MenuCategoriesCollection.php';
    require_once __DIR__ . '/../../../plugins/microschema/content/src/DataSource/ArticleDataSource.php';
    require_once __DIR__ . '/../../../plugins/microschema/content/src/DataType/ArticleDataType.php';
    require_once __DIR__ . '/../../../plugins/microschema/content/src/DataType/ArticleImagesDataType.php';
    require_once __DIR__ . '/../../../plugins/microschema/content/src/Extension/Content.php';

    function assertContentPluginSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message);
        }
    }

    $subscriptions = Content::getSubscribedEvents();

    assertContentPluginSame(
        'prepareForm',
        $subscriptions['onContentPrepareForm'] ?? null,
        'The content plugin must subscribe directly to native form preparation.',
    );
    assertContentPluginSame(
        'registerDataSources',
        $subscriptions[RegisterDataSourcesEvent::NAME] ?? null,
        'The content plugin must subscribe to data source registration.',
    );
    assertContentPluginSame(
        'registerDataCollections',
        $subscriptions[RegisterDataCollectionsEvent::NAME] ?? null,
        'The content plugin must subscribe to collection registration.',
    );
    assertContentPluginSame(
        'registerDataTypes',
        $subscriptions[RegisterDataTypesEvent::NAME] ?? null,
        'The content plugin must subscribe to data type registration.',
    );
    assertContentPluginSame(
        'collectSchemas',
        $subscriptions[CollectSchemasEvent::NAME] ?? null,
        'The content plugin must collect configured article schemas.',
    );
    assertContentPluginSame(
        'prepareData',
        $subscriptions['onContentPrepareData'] ?? null,
        'The content plugin must subscribe to data preparation.',
    );
    assertContentPluginSame(
        'afterSave',
        $subscriptions['onContentAfterSave'] ?? null,
        'The content plugin must subscribe to item saving.',
    );
    assertContentPluginSame(
        'afterDelete',
        $subscriptions['onContentAfterDelete'] ?? null,
        'The content plugin must subscribe to item deletion.',
    );

    $contentManifest = simplexml_load_file(__DIR__ . '/../../../plugins/microschema/content/content.xml');
    $defaultFields   = [];

    foreach ($contentManifest->config->fields->fieldset as $fieldset) {
        foreach ($fieldset->field as $field) {
            $defaultFields[(string) $field['name']] = $field;
        }
    }

    assertContentPluginSame(2, count($contentManifest->config->fields->fieldset), 'The content plugin options must have separate article and category tabs.');
    assertContentPluginSame('com_content.category.articles', (string) $defaultFields['default_item_schema_properties']['context'], 'Global article defaults must preview article sources.');
    assertContentPluginSame('com_content.categories', (string) $defaultFields['default_category_schema_properties']['context'], 'Global category defaults must preview the category source.');

    $siteApplication = new class () implements CMSApplicationInterface {
        public function isClient($identifier): bool
        {
            return $identifier === 'site';
        }
    };
    $administratorApplication = new class () implements CMSApplicationInterface {
        /** @var array<string, mixed> */
        public array $jform = [];

        public function isClient($identifier): bool
        {
            return $identifier === 'administrator';
        }

        public function getInput(): object
        {
            return new class ($this->jform) {
                /** @param array<string, mixed> $jform */
                public function __construct(private readonly array $jform)
                {
                }

                public function get(string $name, mixed $default = null, ?string $filter = null): mixed
                {
                    return $name === 'jform' ? $this->jform : $default;
                }
            };
        }
    };

    $siteModel  = new ItemModel();
    $siteForm   = new Form('com_content.article');
    $sitePlugin = new Content([], $siteModel);
    $sitePlugin->setApplication($siteApplication);
    $sitePlugin->prepareForm(new PrepareFormEvent($siteForm));
    assertContentPluginSame(null, $siteForm->loadedFile, 'The form must not load on the site client.');

    $otherForm    = new Form('com_contact.contact');
    $contentForm  = new Form('com_content.article');
    $categoryForm = new Form('com_categories.categorycom_content');
    $articleMenuForm = new Form('com_menus.item', [
        'link' => 'index.php?option=com_content&view=article&id=42',
    ]);
    $categoryMenuForm = new Form('com_menus.item');
    $featuredMenuForm = new Form('com_menus.item', [
        'link' => 'index.php?option=com_content&view=featured',
    ]);
    $archiveMenuForm = new Form('com_menus.item', [
        'link' => 'index.php?option=com_content&view=archive',
    ]);
    $categoriesMenuForm = new Form('com_menus.item', [
        'link' => 'index.php?option=com_content&view=categories&id=1',
    ]);
    $itemModel    = new ItemModel();
    $plugin       = new Content([], $itemModel);
    $plugin->setApplication($administratorApplication);

    $plugin->prepareForm(new PrepareFormEvent($otherForm));
    assertContentPluginSame(null, $otherForm->loadedFile, 'An unrelated administrator form must not load.');

    $plugin->prepareForm(new PrepareFormEvent($contentForm));
    assertContentPluginSame(
        'article.xml',
        basename((string) $contentForm->loadedFile),
        'The article form must load the MicroSchema form definition.',
    );

    $plugin->prepareForm(new PrepareFormEvent($categoryForm));
    assertContentPluginSame(
        'category.xml',
        basename((string) $categoryForm->loadedFile),
        'The content category form must load its two MicroSchema definitions.',
    );

    $plugin->prepareForm(new PrepareFormEvent($articleMenuForm));
    assertContentPluginSame('menu_article.xml', basename((string) $articleMenuForm->loadedFile), 'A single article menu item must load its menu override form.');

    $plugin->prepareForm(new PrepareFormEvent($categoryMenuForm, [
        'request' => ['option' => 'com_content', 'view' => 'category', 'layout' => 'blog'],
    ]));
    assertContentPluginSame('menu_category.xml', basename((string) $categoryMenuForm->loadedFile), 'Category blog and list menu items must load both menu override scopes.');

    $plugin->prepareForm(new PrepareFormEvent($featuredMenuForm));
    assertContentPluginSame('menu_featured.xml', basename((string) $featuredMenuForm->loadedFile), 'Featured articles must load page and child markup settings.');
    $plugin->prepareForm(new PrepareFormEvent($archiveMenuForm));
    assertContentPluginSame('menu_archive.xml', basename((string) $archiveMenuForm->loadedFile), 'Archived articles must load page and child markup settings.');
    $plugin->prepareForm(new PrepareFormEvent($categoriesMenuForm));
    assertContentPluginSame('menu_categories.xml', basename((string) $categoriesMenuForm->loadedFile), 'The all-categories menu item must load page and category settings.');

    $categoriesMenuXml = simplexml_load_file((string) $categoriesMenuForm->loadedFile);
    $categoriesMenuFields = [];
    foreach ($categoriesMenuXml->fields->fieldset as $fieldset) {
        foreach ($fieldset->field as $field) {
            $categoriesMenuFields[(string) $field['name']] = $field;
        }
    }
    assertContentPluginSame(2, count($categoriesMenuXml->fields->fieldset), 'The all-categories form must have exactly page and category scopes.');
    assertContentPluginSame(false, isset($categoriesMenuFields['item_schema_type']), 'The all-categories form must not define a third-level article override.');
    assertContentPluginSame('PLG_MICROSCHEMA_CONTENT_OPTION_NO_PAGE_SCHEMA', (string) $categoriesMenuFields['page_schema_type']->option[0], 'An empty page type must explicitly mean no page markup.');
    $categoryXml = simplexml_load_file((string) $categoryForm->loadedFile);
    $categoryFields = [];

    foreach ($categoryXml->fields->fieldset as $categoryFieldset) {
        foreach ($categoryFieldset->field as $field) {
            $categoryFields[(string) $field['name']] = $field;
        }
    }

    assertContentPluginSame(2, count($categoryXml->fields->fieldset), 'The content category form must contain two independent fieldsets.');
    assertContentPluginSame('com_content.category.articles', (string) $categoryFields['item_schema_properties']['context'], 'The item template must preview article sources.');
    assertContentPluginSame('com_content.categories', (string) $categoryFields['category_schema_properties']['context'], 'The category schema must use the canonical category context.');
    assertContentPluginSame('__disabled', (string) $categoryFields['item_schema_type']->option[1]['value'], 'Category article templates must support explicit disabling.');
    assertContentPluginSame('PLG_MICROSCHEMA_CONTENT_OPTION_INHERIT', (string) $categoryFields['category_schema_type']->option[0], 'Category pages must inherit by default.');

    $xml        = simplexml_load_file((string) $contentForm->loadedFile);
    $fieldset   = $xml->fields->fieldset;
    $fields     = [];

    foreach ($fieldset->field as $field) {
        $fields[(string) $field['name']] = $field;
    }

    assertContentPluginSame('microschema', (string) $xml->fields['name'], 'Article settings must use their own namespace.');
    assertContentPluginSame('', (string) $fields['schema_type']['onchange'], 'Schema type changes must be reactive.');
    assertContentPluginSame('', (string) $fields['schema_type']->option[0]['value'], 'Default article markup must use the inheritance value.');
    assertContentPluginSame('PLG_MICROSCHEMA_CONTENT_OPTION_INHERIT', (string) $fields['schema_type']->option[0], 'The article form must label its inherited state.');
    assertContentPluginSame('__disabled', (string) $fields['schema_type']->option[1]['value'], 'The article form must provide an explicit disabled state.');
    assertContentPluginSame(
        'schemaOrgProperties',
        (string) $fields['schema_properties']['type'],
        'The article form must contain the dynamic Schema.org properties field.',
    );
    assertContentPluginSame(
        'schema_type',
        (string) $fields['schema_properties']['schemafield'],
        'The properties field must depend on the selected schema type.',
    );
    assertContentPluginSame(
        '',
        (string) $fields['schema_properties']['excludesources'],
        'The article form must not need to hide an unregistered generic item source.',
    );
    assertContentPluginSame(
        '',
        (string) $fields['schema_properties']['reloadtask'],
        'Nested Schema.org fields must not reload the article form.',
    );

    $itemModel->storedParams = [
        'schema_type'       => 'Article',
        'schema_properties' => ['headline' => 'Stored headline'],
    ];
    $articleData      = (object) ['id' => 42, 'title' => 'Article title'];
    $prepareDataEvent = new PrepareDataEvent('com_content.article', $articleData);
    $plugin->prepareData($prepareDataEvent);

    assertContentPluginSame(
        $itemModel->storedParams,
        $prepareDataEvent->getData()->microschema,
        'Stored settings must be loaded into the article form data.',
    );

    $sessionData = (object) [
        'id'          => 42,
        'microschema' => [
            'schema_type'       => 'NewsArticle',
            'schema_properties' => ['headline' => 'Submitted headline'],
        ],
    ];
    $sessionEvent = new PrepareDataEvent('com_content.article', $sessionData);
    $plugin->prepareData($sessionEvent);

    assertContentPluginSame(
        'NewsArticle',
        $sessionEvent->getData()->microschema['schema_type'],
        'Submitted settings must not be replaced after validation errors.',
    );

    $itemModel->storedParams = [
        'schema_type'       => 'Article',
        'schema_properties' => ['articleBody' => '{article.content}'],
    ];
    $partialDataEvent = new PrepareDataEvent(
        'com_content.article',
        (object) ['id' => 42, 'microschema' => ['schema_type' => 'Article']],
    );
    $plugin->prepareData($partialDataEvent);

    assertContentPluginSame(
        [
            'schema_type'       => 'Article',
            'schema_properties' => ['articleBody' => '{article.content}'],
        ],
        $partialDataEvent->getData()->microschema,
        'Stored properties must be merged into partial article form data.',
    );

    $plugin->afterSave(new AfterSaveEvent(
        'com_content.article',
        (object) ['id' => 42],
        [
            'microschema' => [
                'schema_type'       => 'Article',
                'schema_properties' => ['headline' => 'Saved headline'],
            ],
        ],
    ));

    assertContentPluginSame(
        [
            'context' => 'com_content.article',
            'itemId'  => 42,
            'params'  => [
                'schema_type'       => 'Article',
                'schema_properties' => ['headline' => 'Saved headline'],
            ],
        ],
        $itemModel->saved[0] ?? null,
        'Article settings must be saved under their canonical context.',
    );

    $plugin->afterSave(new AfterSaveEvent(
        'com_content.article',
        (object) ['id' => 42],
        ['microschema' => ['schema_type' => '', 'schema_properties' => []]],
    ));

    assertContentPluginSame(
        ['context' => 'com_content.article', 'itemId' => 42],
        $itemModel->deleted[0] ?? null,
        'Clearing the schema type must delete the stored settings.',
    );

    $plugin->afterSave(new AfterSaveEvent('com_content.article', (object) ['id' => 42], []));
    assertContentPluginSame(1, count($itemModel->saved), 'Saves without MicroSchema fields must be ignored.');
    assertContentPluginSame(1, count($itemModel->deleted), 'Saves without MicroSchema fields must not delete settings.');

    $plugin->afterDelete(new AfterDeleteEvent('com_contact.contact', (object) ['id' => 42]));
    assertContentPluginSame(1, count($itemModel->deleted), 'Deleting an unrelated item must not delete article settings.');

    $plugin->afterDelete(new AfterDeleteEvent('com_content.article', (object) ['id' => 42]));
    assertContentPluginSame(
        ['context' => 'com_content.article', 'itemId' => 42],
        $itemModel->deleted[1] ?? null,
        'Deleting an article must permanently delete its stored settings.',
    );

    $itemModel->storedParams = [
        'item_schema_type'            => 'Article',
        'item_schema_properties'      => ['headline' => '{article.title}'],
        'category_schema_type'        => 'CollectionPage',
        'category_schema_properties'  => ['name' => '{category.title}'],
    ];
    $categoryDataEvent = new PrepareDataEvent(
        'com_categories.category',
        (object) ['id' => 9, 'extension' => 'com_content'],
    );
    $plugin->prepareData($categoryDataEvent);
    assertContentPluginSame($itemModel->storedParams, $categoryDataEvent->getData()->microschema, 'Stored content category settings must populate both fieldsets.');

    $plugin->afterSave(new AfterSaveEvent(
        'com_categories.category',
        (object) ['id' => 9, 'extension' => 'com_content'],
        [
            'extension'   => 'com_content',
            'microschema' => [
                'item_schema_type'            => 'Article',
                'item_schema_properties'      => ['headline' => '{article.title}'],
                'category_schema_type'        => '',
                'category_schema_properties'  => [],
            ],
        ],
    ));
    assertContentPluginSame(
        'com_content.categories',
        $itemModel->saved[1]['context'] ?? null,
        'Clearing only the category-page block must preserve and save the article template.',
    );
    $plugin->afterDelete(new AfterDeleteEvent(
        'com_categories.category',
        (object) ['id' => 9, 'extension' => 'com_content'],
    ));
    assertContentPluginSame(
        ['context' => 'com_content.categories', 'itemId' => 9],
        $itemModel->deleted[2] ?? null,
        'Deleting a content category must delete its canonical settings.',
    );

    $disabledPersistenceModel  = new ItemModel();
    $disabledPersistencePlugin = new Content([], $disabledPersistenceModel);
    $disabledPersistencePlugin->setApplication($administratorApplication);
    $disabledPersistencePlugin->afterSave(new AfterSaveEvent(
        'com_content.article',
        (object) ['id' => 77],
        ['microschema' => ['schema_type' => '__disabled', 'schema_properties' => []]],
    ));
    assertContentPluginSame('__disabled', $disabledPersistenceModel->saved[0]['params']['schema_type'] ?? null, 'Explicit article disabling must be persisted instead of treated as inheritance.');
    $disabledPersistencePlugin->afterSave(new AfterSaveEvent(
        'com_categories.category',
        (object) ['id' => 78, 'extension' => 'com_content'],
        [
            'extension'   => 'com_content',
            'microschema' => [
                'item_schema_type'            => '__disabled',
                'item_schema_properties'      => [],
                'category_schema_type'        => '',
                'category_schema_properties'  => [],
            ],
        ],
    ));
    assertContentPluginSame('__disabled', $disabledPersistenceModel->saved[1]['params']['item_schema_type'] ?? null, 'Explicit category-template disabling must be persisted independently.');

    $menuPersistenceModel = new ItemModel();
    $menuPersistenceModel->storedParamsByContext['com_menus.item.com_content:101'] = [
        'schema_type'       => 'NewsArticle',
        'schema_properties' => ['headline' => 'Menu headline'],
    ];
    $menuPersistencePlugin = new Content([], $menuPersistenceModel);
    $menuPersistencePlugin->setApplication($administratorApplication);
    $menuDataEvent = new PrepareDataEvent('com_menus.item', (object) [
        'id'   => 101,
        'link' => 'index.php?option=com_content&view=article&id=42',
    ]);
    $menuPersistencePlugin->prepareData($menuDataEvent);
    assertContentPluginSame('NewsArticle', $menuDataEvent->getData()->microschema['schema_type'] ?? null, 'Stored article menu settings must populate the menu form.');

    $menuPersistencePlugin->afterSave(new AfterSaveEvent(
        'com_menus.item',
        (object) ['id' => 102, 'link' => 'index.php?option=com_content&view=category&id=5'],
        [
            'request' => ['option' => 'com_content', 'view' => 'category', 'layout' => 'blog'],
            'microschema' => [
                'item_schema_type'            => 'BlogPosting',
                'item_schema_properties'      => ['headline' => '{article.title}'],
                'category_schema_type'        => 'CollectionPage',
                'category_schema_properties'  => ['name' => '{category.title}'],
            ],
        ],
    ));
    assertContentPluginSame('com_menus.item.com_content', $menuPersistenceModel->saved[0]['context'] ?? null, 'Content menu settings must use their integration-owned storage context.');
    assertContentPluginSame('CollectionPage', $menuPersistenceModel->saved[0]['params']['category_schema_type'] ?? null, 'A category menu item must persist both override scopes.');

    $administratorApplication->jform = [
        'microschema' => [
            'item_schema_type'            => 'Article',
            'item_schema_properties'      => ['headline' => '{article.title}'],
            'category_schema_type'        => 'CollectionPage',
            'category_schema_properties'  => ['name' => '{category.title}'],
        ],
    ];
    $menuPersistencePlugin->afterSave(new AfterSaveEvent(
        'com_menus.item',
        (object) ['id' => 103, 'link' => 'index.php?option=com_content&view=category&id=5'],
        null,
    ));
    assertContentPluginSame('CollectionPage', $menuPersistenceModel->saved[1]['params']['category_schema_type'] ?? null, 'Menu settings must fall back to the submitted form when the after-save event omits data.');
    $administratorApplication->jform = [];

    $menuPersistencePlugin->afterSave(new AfterSaveEvent(
        'com_menus.item',
        (object) ['id' => 104, 'link' => 'index.php?option=com_content&view=featured'],
        [
            'request' => ['option' => 'com_content', 'view' => 'featured'],
            'microschema' => [
                'page_schema_type'       => 'CollectionPage',
                'page_schema_properties' => ['name' => 'Featured'],
                'item_schema_type'       => 'Article',
                'item_schema_properties' => ['headline' => '{article.title}'],
            ],
        ],
    ));
    assertContentPluginSame('CollectionPage', $menuPersistenceModel->saved[2]['params']['page_schema_type'] ?? null, 'A featured menu item must persist its page schema.');
    assertContentPluginSame('Article', $menuPersistenceModel->saved[2]['params']['item_schema_type'] ?? null, 'A featured menu item must persist its article template.');

    $menuPersistencePlugin->afterSave(new AfterSaveEvent(
        'com_menus.item',
        (object) ['id' => 105, 'link' => 'index.php?option=com_content&view=categories&id=1'],
        [
            'request' => ['option' => 'com_content', 'view' => 'categories', 'id' => 1],
            'microschema' => [
                'page_schema_type'           => 'CollectionPage',
                'page_schema_properties'     => [],
                'category_schema_type'       => 'WebPage',
                'category_schema_properties' => [],
                'item_schema_type'           => 'Article',
            ],
        ],
    ));
    assertContentPluginSame(false, isset($menuPersistenceModel->saved[3]['params']['item_schema_type']), 'An all-categories menu item must not persist an article override.');

    $menuPersistencePlugin->afterDelete(new AfterDeleteEvent('com_menus.item', (object) ['id' => 102]));
    assertContentPluginSame(
        ['context' => 'com_menus.item.com_content', 'itemId' => 102],
        $menuPersistenceModel->deleted[0] ?? null,
        'Deleting a menu item must delete its content override settings.',
    );

    $dataSourcesEvent = new RegisterDataSourcesEvent();
    $plugin->registerDataSources($dataSourcesEvent);

    $dataCollectionsEvent = new RegisterDataCollectionsEvent();
    $plugin->registerDataCollections($dataCollectionsEvent);
    assertContentPluginSame(CategoryArticlesCollection::class, $dataCollectionsEvent->collections[0]::class, 'The content plugin must register its category articles collection.');
    assertContentPluginSame(MenuArticlesCollection::class, $dataCollectionsEvent->collections[1]::class, 'The content plugin must register its menu article collection.');
    assertContentPluginSame(MenuCategoriesCollection::class, $dataCollectionsEvent->collections[2]::class, 'The content plugin must register its menu category collection.');

    assertContentPluginSame(1, count($dataSourcesEvent->sources), 'The article data source must be registered.');
    assertContentPluginSame(
        ArticleDataSource::class,
        $dataSourcesEvent->sources[0]::class,
        'The content plugin must register the article data source.',
    );

    $articleSource  = $dataSourcesEvent->sources[0];
    $articleContext = new DataContext(
        context: 'com_content.article',
        itemId: 42,
        item: (object) [
            'title'  => 'Article title',
            'images' => '{"image_intro":"images/intro.jpg","image_fulltext":"images/full.jpg"}',
        ],
    );
    assertContentPluginSame('article', $articleSource->getName(), 'The article source name must be stable.');
    assertContentPluginSame(
        'PLG_MICROSCHEMA_CONTENT_DATA_SOURCE_ARTICLE',
        $articleSource->getLabel(),
        'The article source label must be translatable.',
    );
    assertContentPluginSame('JoomlaArticle', $articleSource->getType(), 'The article source must reference its reusable type.');
    assertContentPluginSame(
        ContextualDataValue::class,
        $articleSource->getValue($articleContext)::class,
        'The article source must return a contextual root object.',
    );
    assertContentPluginSame($articleContext->item, $articleSource->getValue($articleContext)->value, 'The contextual source must retain the article.');
    $articlePreviewContext = new DataContext('com_content.category.articles', 0, []);
    assertContentPluginSame(true, $articleSource->supportsContext($articlePreviewContext->context), 'The article source must be available while configuring a category template.');
    assertContentPluginSame('com_content.article', $articleSource->getValue($articlePreviewContext)->context, 'The preview value must retain the runtime article context.');

    $dataTypesEvent = new RegisterDataTypesEvent();
    $plugin->registerDataTypes($dataTypesEvent);

    assertContentPluginSame(2, count($dataTypesEvent->types), 'The content plugin must register only its integration-specific object types.');
    assertContentPluginSame(ArticleDataType::class, $dataTypesEvent->types[0]::class, 'The article type must be registered.');

    $articleType = $dataTypesEvent->types[0];
    $articleValue = $articleSource->getValue($articleContext);
    $fields = $articleType->getFields($articleValue, $articleContext);
    $fieldsByName = [];

    foreach ($fields as $field) {
        $fieldsByName[$field->name] = $field;
    }

    assertContentPluginSame('JoomlaUser', $fieldsByName['author']->type ?? null, 'The author must reference the shared user type.');
    assertContentPluginSame('JoomlaCategory', $fieldsByName['category']->type ?? null, 'The category must reference the shared category type.');
    assertContentPluginSame('JoomlaArticleImages', $fieldsByName['images']->type ?? null, 'Images must reference their own type.');
    assertContentPluginSame('JoomlaCustomFields', $fieldsByName['fields']->type ?? null, 'Custom fields must belong to the article object.');
    assertContentPluginSame(
        'Article title',
        $articleType->resolve($articleValue, 'title', $articleContext),
        'The article type must resolve only its own direct field.',
    );
    $imagesType = $dataTypesEvent->types[1];
    assertContentPluginSame(ArticleImagesDataType::class, $imagesType::class, 'The image type must be registered independently.');
    assertContentPluginSame(
        'images/intro.jpg',
        $imagesType->resolve($articleType->resolve($articleValue, 'images', $articleContext), 'image_intro', $articleContext),
        'The image type must resolve its own field.',
    );

    $registryArticle = new Registry(['title' => 'Registry article']);
    $registryValue = new ContextualDataValue('com_content.article', 42, $registryArticle);
    assertContentPluginSame('Registry article', $articleType->resolve($registryValue, 'title', $articleContext), 'Registry article fields must resolve through Registry::get().');
    assertContentPluginSame(null, $articleType->resolve($registryValue, 'author', $articleContext), 'A missing Registry article author must resolve to null.');
    assertContentPluginSame(null, $articleType->resolve($registryValue, 'category', $articleContext), 'A missing Registry article category must resolve to null.');
    assertContentPluginSame(null, $articleType->resolve($registryValue, 'images', $articleContext), 'Missing Registry article images must resolve to null.');
    assertContentPluginSame(0, $registryArticle->magicReads, 'Article resolution must not invoke Registry::__get().');

    $unsupportedContext = new DataContext('com_contact.contact', 1, ['title' => 'Contact']);

    assertContentPluginSame(false, $articleSource->supportsContext($unsupportedContext->context), 'The source context must be strict.');
    assertContentPluginSame(null, $articleSource->getValue($unsupportedContext), 'Unsupported contexts must not expose a root value.');

    $runtimeModel = new ItemModel();
    $runtimeModel->storedParamsByContext['com_content.categories:5'] = [
        'item_schema_type'       => 'Article',
        'item_schema_properties' => ['name' => '{article.title}'],
    ];
    $runtimeArticle = (object) [
        'id'           => 42,
        'catid'        => 5,
        'title'        => 'Runtime article title',
        'customFields' => [],
        'params'       => new class () {
            public function get(string $name, mixed $default = null): mixed
            {
                return $name === 'access-view' ? true : $default;
            }
        },
    ];
    $rootCategory = new class () {
        public int $id = 1;

        public function getParent(): ?object
        {
            return null;
        }
    };
    $parentCategory = new class ($rootCategory) {
        public int $id = 4;

        public function __construct(private readonly object $parent)
        {
        }

        public function getParent(): object
        {
            return $this->parent;
        }
    };
    $childCategory = new class ($parentCategory) {
        public int $id = 5;

        public function __construct(private readonly object $parent)
        {
        }

        public function getParent(): object
        {
            return $this->parent;
        }
    };
    Categories::$items = [1 => $rootCategory, 4 => $parentCategory, 5 => $childCategory];
    $runtimeApplication = new class ($runtimeArticle) implements CMSApplicationInterface {
        public ?object $activeMenuItem = null;

        public function __construct(private readonly object $article)
        {
        }

        public function isClient($identifier): bool
        {
            return $identifier === 'site';
        }

        public function getInput(): object
        {
            return new class () {
                public function getCmd(string $name): string
                {
                    return match ($name) {
                        'option' => 'com_content',
                        'view'   => 'article',
                        default  => '',
                    };
                }

                public function getInt(string $name): int
                {
                    return $name === 'id' ? 42 : 0;
                }
            };
        }

        public function getMenu(): object
        {
            $active = $this->activeMenuItem;

            return new class ($active) {
                public function __construct(private readonly ?object $active)
                {
                }

                public function getActive(): ?object
                {
                    return $this->active;
                }
            };
        }

        public function bootComponent(string $name): object
        {
            if ($name === 'com_microschema') {
                return new MicroschemaComponent();
            }

            $article = $this->article;

            return new class ($article) {
                public function __construct(private readonly object $article)
                {
                }

                public function getMVCFactory(): object
                {
                    $article = $this->article;

                    return new class ($article) {
                        public function __construct(private readonly object $article)
                        {
                        }

                        public function createModel(string $name, string $client, array $options): object
                        {
                            $article = $this->article;

                            return new class ($article) {
                                public function __construct(private readonly object $article)
                                {
                                }

                                public function getItem(int $itemId): object
                                {
                                    return $this->article;
                                }
                            };
                        }
                    };
                }
            };
        }
    };
    $runtimePlugin = new Content([
        'params' => [
            'default_item_schema_type'       => 'BlogPosting',
            'default_item_schema_properties' => ['name' => 'Global article'],
        ],
    ], $runtimeModel);
    $runtimePlugin->setApplication($runtimeApplication);
    $collectEvent = new CollectSchemasEvent();
    $runtimePlugin->collectSchemas($collectEvent);

    assertContentPluginSame(
        [['context' => 'com_content.article', 'id' => 42]],
        $collectEvent->contexts,
        'The current article must be appended to the schema context path.',
    );
    assertContentPluginSame(
        'Runtime article title',
        $collectEvent->schemas[0]['data']['name'] ?? null,
        'The directly assigned category template must resolve against the current article.',
    );

    $directRuntimeModel = new ItemModel();
    $directRuntimeModel->storedParamsByContext = [
        'com_content.article:42' => [
            'schema_type'       => 'NewsArticle',
            'schema_properties' => ['name' => 'Individual article'],
        ],
        'com_content.categories:5' => [
            'item_schema_type'       => '__disabled',
            'item_schema_properties' => [],
        ],
    ];
    $directRuntimePlugin = new Content([
        'params' => [
            'default_item_schema_type'       => 'BlogPosting',
            'default_item_schema_properties' => ['name' => 'Global article'],
        ],
    ], $directRuntimeModel);
    $directRuntimePlugin->setApplication($runtimeApplication);
    $directCollectEvent = new CollectSchemasEvent();
    $directRuntimePlugin->collectSchemas($directCollectEvent);
    assertContentPluginSame('NewsArticle', $directCollectEvent->schemas[0]['data']['@type'] ?? null, 'Individual article settings must fully replace the category template.');
    assertContentPluginSame('Individual article', $directCollectEvent->schemas[0]['data']['name'] ?? null, 'Category values must not leak into an individual article schema.');

    $runtimeApplication->activeMenuItem = (object) [
        'id'    => 100,
        'query' => ['option' => 'com_content', 'view' => 'category', 'id' => 5],
    ];
    $menuOverrideModel = new ItemModel();
    $menuOverrideModel->storedParamsByContext = [
        'com_content.article:42' => [
            'schema_type'       => 'NewsArticle',
            'schema_properties' => ['name' => 'Individual article'],
        ],
        'com_menus.item.com_content:100' => [
            'item_schema_type'       => 'BlogPosting',
            'item_schema_properties' => ['name' => 'Category menu article'],
        ],
    ];
    $menuOverridePlugin = new Content([], $menuOverrideModel);
    $menuOverridePlugin->setApplication($runtimeApplication);
    $menuOverrideEvent = new CollectSchemasEvent();
    $menuOverridePlugin->collectSchemas($menuOverrideEvent);
    assertContentPluginSame('Category menu article', $menuOverrideEvent->schemas[0]['data']['name'] ?? null, 'The active category menu article template must override individual article settings.');
    assertContentPluginSame('com_menus.item:100', $menuOverrideEvent->schemas[0]['contextKey'] ?? null, 'A menu override must be registered after the current article context.');

    $menuOverrideModel->storedParamsByContext['com_menus.item.com_content:100']['item_schema_type'] = '__disabled';
    $disabledMenuEvent = new CollectSchemasEvent();
    $menuOverridePlugin->collectSchemas($disabledMenuEvent);
    assertContentPluginSame([], $disabledMenuEvent->schemas, 'A disabled active menu override must stop article, category, and global fallback.');

    $runtimeApplication->activeMenuItem = (object) [
        'id'    => 106,
        'query' => ['option' => 'com_content', 'view' => 'featured'],
    ];
    $menuOverrideModel->storedParamsByContext['com_menus.item.com_content:106'] = [
        'item_schema_type'       => 'Article',
        'item_schema_properties' => ['name' => 'Featured menu article'],
    ];
    $featuredChildEvent = new CollectSchemasEvent();
    $menuOverridePlugin->collectSchemas($featuredChildEvent);
    assertContentPluginSame('Featured menu article', $featuredChildEvent->schemas[0]['data']['name'] ?? null, 'A featured menu article template must override individual article settings.');
    $runtimeApplication->activeMenuItem = null;

    $parentRuntimeModel = new ItemModel();
    $parentRuntimeModel->storedParamsByContext['com_content.categories:4'] = [
        'item_schema_type'       => 'Article',
        'item_schema_properties' => ['name' => 'Inherited from parent category'],
    ];
    $parentRuntimePlugin = new Content([
        'params' => [
            'default_item_schema_type'       => 'BlogPosting',
            'default_item_schema_properties' => ['name' => 'Global article'],
        ],
    ], $parentRuntimeModel);
    $parentRuntimePlugin->setApplication($runtimeApplication);
    $parentCollectEvent = new CollectSchemasEvent();
    $parentRuntimePlugin->collectSchemas($parentCollectEvent);
    assertContentPluginSame('Inherited from parent category', $parentCollectEvent->schemas[0]['data']['name'] ?? null, 'Default category settings must continue through parent categories before the global template.');

    $disabledRuntimeModel = new ItemModel();
    $disabledRuntimeModel->storedParamsByContext['com_content.categories:5'] = [
        'item_schema_type'       => '__disabled',
        'item_schema_properties' => [],
    ];
    $disabledRuntimePlugin = new Content([
        'params' => [
            'default_item_schema_type'       => 'BlogPosting',
            'default_item_schema_properties' => ['name' => 'Global article'],
        ],
    ], $disabledRuntimeModel);
    $disabledRuntimePlugin->setApplication($runtimeApplication);
    $disabledCollectEvent = new CollectSchemasEvent();
    $disabledRuntimePlugin->collectSchemas($disabledCollectEvent);
    assertContentPluginSame([], $disabledCollectEvent->schemas, 'Disabled category markup must stop article inheritance before the global template.');

    $disabledArticleModel = new ItemModel();
    $disabledArticleModel->storedParamsByContext['com_content.article:42'] = [
        'schema_type'       => '__disabled',
        'schema_properties' => [],
    ];
    $disabledArticleModel->storedParamsByContext['com_content.categories:5'] = [
        'item_schema_type'       => 'Article',
        'item_schema_properties' => ['name' => 'Category article'],
    ];
    $disabledArticlePlugin = new Content([
        'params' => [
            'default_item_schema_type'       => 'BlogPosting',
            'default_item_schema_properties' => ['name' => 'Global article'],
        ],
    ], $disabledArticleModel);
    $disabledArticlePlugin->setApplication($runtimeApplication);
    $disabledArticleEvent = new CollectSchemasEvent();
    $disabledArticlePlugin->collectSchemas($disabledArticleEvent);
    assertContentPluginSame([], $disabledArticleEvent->schemas, 'Disabled article markup must stop category and global inheritance.');

    $runtimeCategoryModel = new ItemModel();
    $runtimeCategoryModel->storedParamsByContext['com_content.categories:5'] = [
        'category_schema_type'       => 'CollectionPage',
        'category_schema_properties' => ['name' => '{category.title}'],
    ];
    $runtimeCategory = (object) ['id' => 5, 'title' => 'News', 'language' => '*'];
    $runtimeCategoryApplication = new class ($runtimeCategory) implements CMSApplicationInterface {
        public ?object $activeMenuItem = null;

        public function __construct(private readonly object $category)
        {
        }

        public function isClient($identifier): bool
        {
            return $identifier === 'site';
        }

        public function getInput(): object
        {
            return new class () {
                public function getCmd(string $name): string
                {
                    return match ($name) {
                        'option' => 'com_content',
                        'view'   => 'category',
                        default  => '',
                    };
                }

                public function getInt(string $name): int
                {
                    return $name === 'id' ? 5 : 0;
                }
            };
        }

        public function getMenu(): object
        {
            $active = $this->activeMenuItem;

            return new class ($active) {
                public function __construct(private readonly ?object $active)
                {
                }

                public function getActive(): ?object
                {
                    return $this->active;
                }
            };
        }

        public function bootComponent(string $name): object
        {
            if ($name === 'com_microschema') {
                return new MicroschemaComponent();
            }

            $category = $this->category;

            return new class ($category) {
                public function __construct(private readonly object $category)
                {
                }

                public function getMVCFactory(): object
                {
                    $category = $this->category;

                    return new class ($category) {
                        public function __construct(private readonly object $category)
                        {
                        }

                        public function createModel(string $name, string $client, array $options): object
                        {
                            $category = $this->category;

                            return new class ($category) {
                                public function __construct(private readonly object $category)
                                {
                                }

                                public function getCategory(): object
                                {
                                    return $this->category;
                                }

                                public function getItems(): array
                                {
                                    return [(object) ['id' => 51, 'title' => 'First category article']];
                                }

                                public function getPagination(): object
                                {
                                    return (object) ['limitstart' => 25, 'total' => 40];
                                }
                            };
                        }
                    };
                }
            };
        }
    };
    $runtimeCategoryPlugin = new Content([
        'params' => [
            'default_category_schema_type'       => 'WebPage',
            'default_category_schema_properties' => ['name' => 'Global category'],
        ],
    ], $runtimeCategoryModel);
    $runtimeCategoryPlugin->setApplication($runtimeCategoryApplication);
    $categoryCollectEvent = new CollectSchemasEvent();
    $runtimeCategoryPlugin->collectSchemas($categoryCollectEvent);
    assertContentPluginSame('News', $categoryCollectEvent->schemas[0]['data']['name'] ?? null, 'The content category page must resolve its own category source.');
    assertContentPluginSame('com_content.categories:5', $categoryCollectEvent->schemas[0]['contextKey'] ?? null, 'The category schema must be registered under its canonical context.');

    $runtimeCategoryApplication->activeMenuItem = (object) [
        'id'    => 103,
        'query' => ['option' => 'com_content', 'view' => 'category', 'id' => 5],
    ];
    $menuCategoryModel = new ItemModel();
    $menuCategoryModel->storedParamsByContext = [
        'com_content.categories:5' => [
            'category_schema_type'       => 'CollectionPage',
            'category_schema_properties' => ['name' => 'Category entity'],
        ],
        'com_menus.item.com_content:103' => [
            'category_schema_type'       => 'WebPage',
            'category_schema_properties' => ['name' => 'Category menu page'],
        ],
    ];
    $menuCategoryPlugin = new Content([], $menuCategoryModel);
    $menuCategoryPlugin->setApplication($runtimeCategoryApplication);
    $menuCategoryEvent = new CollectSchemasEvent();
    $menuCategoryPlugin->collectSchemas($menuCategoryEvent);
    assertContentPluginSame('Category menu page', $menuCategoryEvent->schemas[0]['data']['name'] ?? null, 'The active category menu page settings must override category inheritance.');
    assertContentPluginSame('com_menus.item:103', $menuCategoryEvent->schemas[0]['contextKey'] ?? null, 'A category page menu override must be the most specific context.');
    $runtimeCategoryApplication->activeMenuItem = null;

    $parentCategoryPageModel = new ItemModel();
    $parentCategoryPageModel->storedParamsByContext['com_content.categories:4'] = [
        'category_schema_type'       => 'CollectionPage',
        'category_schema_properties' => ['name' => 'Inherited category page'],
    ];
    $parentCategoryPagePlugin = new Content([
        'params' => [
            'default_category_schema_type'       => 'WebPage',
            'default_category_schema_properties' => ['name' => 'Global category'],
        ],
    ], $parentCategoryPageModel);
    $parentCategoryPagePlugin->setApplication($runtimeCategoryApplication);
    $parentCategoryPageEvent = new CollectSchemasEvent();
    $parentCategoryPagePlugin->collectSchemas($parentCategoryPageEvent);
    assertContentPluginSame('Inherited category page', $parentCategoryPageEvent->schemas[0]['data']['name'] ?? null, 'Category pages must inherit markup through their parent categories.');

    $disabledCategoryPageModel = new ItemModel();
    $disabledCategoryPageModel->storedParamsByContext['com_content.categories:5'] = [
        'category_schema_type'       => '__disabled',
        'category_schema_properties' => [],
    ];
    $disabledCategoryPagePlugin = new Content([
        'params' => [
            'default_category_schema_type'       => 'WebPage',
            'default_category_schema_properties' => ['name' => 'Global category'],
        ],
    ], $disabledCategoryPageModel);
    $disabledCategoryPagePlugin->setApplication($runtimeCategoryApplication);
    $disabledCategoryPageEvent = new CollectSchemasEvent();
    $disabledCategoryPagePlugin->collectSchemas($disabledCategoryPageEvent);
    assertContentPluginSame([], $disabledCategoryPageEvent->schemas, 'Disabled category-page markup must stop parent and global inheritance.');

    $globalArticlePlugin = new Content([
        'params' => [
            'default_item_schema_type'       => 'BlogPosting',
            'default_item_schema_properties' => ['name' => '{article.title}'],
        ],
    ], new ItemModel());
    $globalArticlePlugin->setApplication($runtimeApplication);
    $globalArticleEvent = new CollectSchemasEvent();
    $globalArticlePlugin->collectSchemas($globalArticleEvent);
    assertContentPluginSame('BlogPosting', $globalArticleEvent->schemas[0]['data']['@type'] ?? null, 'The global article template must be the final item fallback.');
    assertContentPluginSame('Runtime article title', $globalArticleEvent->schemas[0]['data']['name'] ?? null, 'Global article placeholders must resolve against the current article.');

    $globalCategoryPlugin = new Content([
        'params' => [
            'default_category_schema_type'       => 'CollectionPage',
            'default_category_schema_properties' => ['name' => '{category.title}'],
        ],
    ], new ItemModel());
    $globalCategoryPlugin->setApplication($runtimeCategoryApplication);
    $globalCategoryEvent = new CollectSchemasEvent();
    $globalCategoryPlugin->collectSchemas($globalCategoryEvent);
    assertContentPluginSame('News', $globalCategoryEvent->schemas[0]['data']['name'] ?? null, 'The global content category template must resolve against the current category.');
    $runtimeCollectionsEvent = new RegisterDataCollectionsEvent();
    $runtimeCategoryPlugin->registerDataCollections($runtimeCollectionsEvent);
    $runtimeCollectionResult = $runtimeCollectionsEvent->collections[0]->getItems(
        new DataContext('com_content.categories', 5, $runtimeCategory),
    );
    assertContentPluginSame('com_content.article', $runtimeCollectionResult->items[0]->context ?? null, 'The articles collection must return article data contexts.');
    assertContentPluginSame(25, $runtimeCollectionResult->offset, 'The articles collection must preserve the current pagination offset.');
    assertContentPluginSame(40, $runtimeCollectionResult->total, 'The articles collection must expose the category total.');

    $menuPageApplication = new class () implements CMSApplicationInterface {
        private object $active;

        public function __construct()
        {
            $this->active = (object) [
                'id'    => 110,
                'title' => 'Featured articles',
                'query' => ['option' => 'com_content', 'view' => 'featured'],
            ];
        }

        public function isClient($identifier): bool
        {
            return $identifier === 'site';
        }

        public function getInput(): object
        {
            return new class () {
                public function getCmd(string $name): string
                {
                    return $name === 'option' ? 'com_content' : ($name === 'view' ? 'featured' : '');
                }
            };
        }

        public function getMenu(): object
        {
            return new class ($this->active) {
                public function __construct(private readonly object $active)
                {
                }

                public function getActive(): object
                {
                    return $this->active;
                }
            };
        }

        public function bootComponent(string $name): object
        {
            return new MicroschemaComponent();
        }
    };
    $menuPageModel = new ItemModel();
    $menuPageModel->storedParamsByContext['com_menus.item.com_content:110'] = [
        'page_schema_type'       => 'CollectionPage',
        'page_schema_properties' => ['name' => 'Featured articles page'],
    ];
    $menuPagePlugin = new Content([], $menuPageModel);
    $menuPagePlugin->setApplication($menuPageApplication);
    $menuPageEvent = new CollectSchemasEvent();
    $menuPagePlugin->collectSchemas($menuPageEvent);
    assertContentPluginSame('Featured articles page', $menuPageEvent->schemas[0]['data']['name'] ?? null, 'An explicit featured page schema must be collected.');
    assertContentPluginSame('com_menus.item:110', $menuPageEvent->schemas[0]['contextKey'] ?? null, 'A menu page schema must use the menu override context.');

    echo "Content plugin tests passed.\n";
}
