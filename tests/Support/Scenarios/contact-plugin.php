<?php

declare(strict_types=1);

namespace Joomla\Component\Contact\Site\Helper {
    final class RouteHelper
    {
        public static function getContactRoute(int|string $id, int $categoryId = 0, string $language = '*'): string
        {
            return 'contact/' . $id;
        }

        public static function getCategoryRoute(int $id, string $language = '*'): string
        {
            return 'contact-category/' . $id;
        }
    }
}

namespace {
    require __DIR__ . '/content-plugin.php';

    use Joomla\CMS\Application\CMSApplicationInterface;
    use Joomla\CMS\Categories\Categories;
    use Joomla\CMS\Event\Model\AfterDeleteEvent;
    use Joomla\CMS\Event\Model\AfterSaveEvent;
    use Joomla\CMS\Event\Model\PrepareDataEvent;
    use Joomla\CMS\Event\Model\PrepareFormEvent;
    use Joomla\CMS\Form\Form;
    use Joomla\Component\Microschema\Administrator\DataSource\ContextualDataValue;
    use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
    use Joomla\Component\Microschema\Administrator\Event\CollectSchemasEvent;
    use Joomla\Component\Microschema\Administrator\Event\RegisterDataCollectionsEvent;
    use Joomla\Component\Microschema\Administrator\Event\RegisterDataSourcesEvent;
    use Joomla\Component\Microschema\Administrator\Event\RegisterDataTypesEvent;
    use Joomla\Component\Microschema\Administrator\Extension\MicroschemaComponent;
    use Joomla\Component\Microschema\Administrator\Model\ItemModel;
    use Joomla\Plugin\Microschema\Contact\DataSource\ContactDataSource;
    use Joomla\Plugin\Microschema\Contact\DataCollection\CategoryContactsCollection;
    use Joomla\Plugin\Microschema\Contact\DataCollection\MenuCategoriesCollection;
    use Joomla\Plugin\Microschema\Contact\DataCollection\MenuContactsCollection;
    use Joomla\Plugin\Microschema\Contact\DataType\ContactDataType;
    use Joomla\Plugin\Microschema\Contact\Extension\Contact;
    use Joomla\Registry\Registry;

    require_once __DIR__ . '/../../../plugins/microschema/contact/src/DataCollection/CategoryContactsCollection.php';
    require_once __DIR__ . '/../../../plugins/microschema/contact/src/DataCollection/MenuCategoriesCollection.php';
    require_once __DIR__ . '/../../../plugins/microschema/contact/src/DataCollection/MenuContactsCollection.php';
    require_once __DIR__ . '/../../../plugins/microschema/contact/src/DataSource/ContactDataSource.php';
    require_once __DIR__ . '/../../../plugins/microschema/contact/src/DataType/ContactDataType.php';
    require_once __DIR__ . '/../../../plugins/microschema/contact/src/Extension/Contact.php';

    function assertContactPluginSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message);
        }
    }

    $subscriptions = Contact::getSubscribedEvents();

    assertContactPluginSame('prepareForm', $subscriptions['onContentPrepareForm'] ?? null, 'The contact plugin must prepare the native contact form.');
    assertContactPluginSame('prepareData', $subscriptions['onContentPrepareData'] ?? null, 'The contact plugin must prepare stored contact data.');
    assertContactPluginSame('afterSave', $subscriptions['onContentAfterSave'] ?? null, 'The contact plugin must save contact settings.');
    assertContactPluginSame('afterDelete', $subscriptions['onContentAfterDelete'] ?? null, 'The contact plugin must delete contact settings.');
    assertContactPluginSame('registerDataSources', $subscriptions[RegisterDataSourcesEvent::NAME] ?? null, 'The contact plugin must register its source.');
    assertContactPluginSame('registerDataCollections', $subscriptions[RegisterDataCollectionsEvent::NAME] ?? null, 'The contact plugin must register its collection.');
    assertContactPluginSame('registerDataTypes', $subscriptions[RegisterDataTypesEvent::NAME] ?? null, 'The contact plugin must register its type.');
    assertContactPluginSame('collectSchemas', $subscriptions[CollectSchemasEvent::NAME] ?? null, 'The contact plugin must collect contact schemas.');

    $contactManifest = simplexml_load_file(__DIR__ . '/../../../plugins/microschema/contact/contact.xml');
    $defaultFields   = [];

    foreach ($contactManifest->config->fields->fieldset as $fieldset) {
        foreach ($fieldset->field as $field) {
            $defaultFields[(string) $field['name']] = $field;
        }
    }

    assertContactPluginSame(2, count($contactManifest->config->fields->fieldset), 'The contact plugin options must have separate contact and category tabs.');
    assertContactPluginSame('com_contact.category.contacts', (string) $defaultFields['default_item_schema_properties']['context'], 'Global contact defaults must preview contact sources.');
    assertContactPluginSame('com_contact.categories', (string) $defaultFields['default_category_schema_properties']['context'], 'Global category defaults must preview the category source.');

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
    $itemModel  = new ItemModel();
    $plugin     = new Contact([], $itemModel);
    $contactForm  = new Form('com_contact.contact');
    $categoryForm = new Form('com_categories.categorycom_contact');
    $otherForm    = new Form('com_content.article');
    $contactMenuForm = new Form('com_menus.item', [
        'link' => 'index.php?option=com_contact&view=contact&id=7',
    ]);
    $categoryMenuForm = new Form('com_menus.item');
    $featuredMenuForm = new Form('com_menus.item', [
        'link' => 'index.php?option=com_contact&view=featured',
    ]);
    $categoriesMenuForm = new Form('com_menus.item', [
        'link' => 'index.php?option=com_contact&view=categories&id=1',
    ]);
    $plugin->setApplication($administratorApplication);

    $plugin->prepareForm(new PrepareFormEvent($otherForm));
    assertContactPluginSame(null, $otherForm->loadedFile, 'The contact plugin must ignore unrelated forms.');

    $plugin->prepareForm(new PrepareFormEvent($contactForm));
    assertContactPluginSame('contact.xml', basename((string) $contactForm->loadedFile), 'The contact form must load its MicroSchema definition.');

    $plugin->prepareForm(new PrepareFormEvent($categoryForm));
    assertContactPluginSame('category.xml', basename((string) $categoryForm->loadedFile), 'The contact category form must load its MicroSchema definitions.');

    $plugin->prepareForm(new PrepareFormEvent($contactMenuForm));
    assertContactPluginSame('menu_contact.xml', basename((string) $contactMenuForm->loadedFile), 'A single contact menu item must load its menu override form.');

    $plugin->prepareForm(new PrepareFormEvent($categoryMenuForm, [
        'request' => ['option' => 'com_contact', 'view' => 'category'],
    ]));
    assertContactPluginSame('menu_category.xml', basename((string) $categoryMenuForm->loadedFile), 'A contact category menu item must load both menu override scopes.');

    $plugin->prepareForm(new PrepareFormEvent($featuredMenuForm));
    assertContactPluginSame('menu_featured.xml', basename((string) $featuredMenuForm->loadedFile), 'Featured contacts must load page and child markup settings.');
    $plugin->prepareForm(new PrepareFormEvent($categoriesMenuForm));
    assertContactPluginSame('menu_categories.xml', basename((string) $categoriesMenuForm->loadedFile), 'The all-contact-categories menu item must load page and category settings.');

    $categoriesMenuXml = simplexml_load_file((string) $categoriesMenuForm->loadedFile);
    $categoriesMenuFields = [];
    foreach ($categoriesMenuXml->fields->fieldset as $fieldset) {
        foreach ($fieldset->field as $field) {
            $categoriesMenuFields[(string) $field['name']] = $field;
        }
    }
    assertContactPluginSame(2, count($categoriesMenuXml->fields->fieldset), 'The all-contact-categories form must have exactly page and category scopes.');
    assertContactPluginSame(false, isset($categoriesMenuFields['item_schema_type']), 'The all-contact-categories form must not define a third-level contact override.');
    assertContactPluginSame('PLG_MICROSCHEMA_CONTACT_OPTION_NO_PAGE_SCHEMA', (string) $categoriesMenuFields['page_schema_type']->option[0], 'An empty page type must explicitly mean no page markup.');
    $categoryXml = simplexml_load_file((string) $categoryForm->loadedFile);
    $categoryFields = [];

    foreach ($categoryXml->fields->fieldset as $categoryFieldset) {
        foreach ($categoryFieldset->field as $field) {
            $categoryFields[(string) $field['name']] = $field;
        }
    }

    assertContactPluginSame(2, count($categoryXml->fields->fieldset), 'The contact category form must contain two independent fieldsets.');
    assertContactPluginSame('com_contact.category.contacts', (string) $categoryFields['item_schema_properties']['context'], 'The item template must preview contact sources.');
    assertContactPluginSame('com_contact.categories', (string) $categoryFields['category_schema_properties']['context'], 'The category schema must use the canonical contact category context.');
    assertContactPluginSame('__disabled', (string) $categoryFields['item_schema_type']->option[1]['value'], 'Category contact templates must support explicit disabling.');
    assertContactPluginSame('PLG_MICROSCHEMA_CONTACT_OPTION_INHERIT', (string) $categoryFields['category_schema_type']->option[0], 'Contact category pages must inherit by default.');

    $xml      = simplexml_load_file((string) $contactForm->loadedFile);
    $fieldset = $xml->fields->fieldset;
    $fields   = [];

    foreach ($fieldset->field as $field) {
        $fields[(string) $field['name']] = $field;
    }

    assertContactPluginSame('microschema', (string) $xml->fields['name'], 'Contact settings must use their own namespace.');
    assertContactPluginSame('com_contact.contact', (string) $fields['schema_properties']['context'], 'The properties field must use the canonical contact context.');
    assertContactPluginSame('schema_type', (string) $fields['schema_properties']['schemafield'], 'The properties field must follow the selected schema type.');
    assertContactPluginSame('PLG_MICROSCHEMA_CONTACT_OPTION_INHERIT', (string) $fields['schema_type']->option[0], 'The contact form must label its inherited state.');
    assertContactPluginSame('__disabled', (string) $fields['schema_type']->option[1]['value'], 'The contact form must provide an explicit disabled state.');

    $itemModel->storedParams = [
        'schema_type'       => 'Person',
        'schema_properties' => ['name' => '{contact.name}'],
    ];
    $prepareDataEvent = new PrepareDataEvent('com_contact.contact', (object) ['id' => 7, 'name' => 'Contact name']);
    $plugin->prepareData($prepareDataEvent);

    assertContactPluginSame($itemModel->storedParams, $prepareDataEvent->getData()->microschema, 'Stored settings must be loaded into the contact form.');

    $plugin->afterSave(new AfterSaveEvent(
        'com_contact.contact',
        (object) ['id' => 7],
        ['microschema' => ['schema_type' => 'Person', 'schema_properties' => ['name' => '{contact.name}']]],
    ));
    assertContactPluginSame(
        [
            'context' => 'com_contact.contact',
            'itemId'  => 7,
            'params'  => ['schema_type' => 'Person', 'schema_properties' => ['name' => '{contact.name}']],
        ],
        $itemModel->saved[0] ?? null,
        'Contact settings must be saved under their canonical context.',
    );

    $plugin->afterSave(new AfterSaveEvent(
        'com_contact.contact',
        (object) ['id' => 7],
        ['microschema' => ['schema_type' => '', 'schema_properties' => []]],
    ));
    assertContactPluginSame(
        ['context' => 'com_contact.contact', 'itemId' => 7],
        $itemModel->deleted[0] ?? null,
        'Clearing the schema type must delete stored contact settings.',
    );

    $plugin->afterDelete(new AfterDeleteEvent('com_contact.contact', (object) ['id' => 7]));
    assertContactPluginSame(
        ['context' => 'com_contact.contact', 'itemId' => 7],
        $itemModel->deleted[1] ?? null,
        'Deleting a contact must delete its settings.',
    );

    $itemModel->storedParams = [
        'item_schema_type'            => 'Person',
        'item_schema_properties'      => ['name' => '{contact.name}'],
        'category_schema_type'        => 'CollectionPage',
        'category_schema_properties'  => ['name' => '{category.title}'],
    ];
    $categoryDataEvent = new PrepareDataEvent(
        'com_categories.category',
        (object) ['id' => 3, 'extension' => 'com_contact'],
    );
    $plugin->prepareData($categoryDataEvent);
    assertContactPluginSame($itemModel->storedParams, $categoryDataEvent->getData()->microschema, 'Stored contact category settings must populate both fieldsets.');

    $plugin->afterSave(new AfterSaveEvent(
        'com_categories.category',
        (object) ['id' => 3, 'extension' => 'com_contact'],
        [
            'extension'   => 'com_contact',
            'microschema' => [
                'item_schema_type'            => '',
                'item_schema_properties'      => [],
                'category_schema_type'        => 'CollectionPage',
                'category_schema_properties'  => ['name' => '{category.title}'],
            ],
        ],
    ));
    assertContactPluginSame('com_contact.categories', $itemModel->saved[1]['context'] ?? null, 'Clearing only the contact template must preserve the category-page schema.');
    $plugin->afterDelete(new AfterDeleteEvent(
        'com_categories.category',
        (object) ['id' => 3, 'extension' => 'com_contact'],
    ));
    assertContactPluginSame(
        ['context' => 'com_contact.categories', 'itemId' => 3],
        $itemModel->deleted[2] ?? null,
        'Deleting a contact category must delete its canonical settings.',
    );

    $disabledPersistenceModel  = new ItemModel();
    $disabledPersistencePlugin = new Contact([], $disabledPersistenceModel);
    $disabledPersistencePlugin->setApplication($administratorApplication);
    $disabledPersistencePlugin->afterSave(new AfterSaveEvent(
        'com_contact.contact',
        (object) ['id' => 17],
        ['microschema' => ['schema_type' => '__disabled', 'schema_properties' => []]],
    ));
    assertContactPluginSame('__disabled', $disabledPersistenceModel->saved[0]['params']['schema_type'] ?? null, 'Explicit contact disabling must be persisted instead of treated as inheritance.');
    $disabledPersistencePlugin->afterSave(new AfterSaveEvent(
        'com_categories.category',
        (object) ['id' => 18, 'extension' => 'com_contact'],
        [
            'extension'   => 'com_contact',
            'microschema' => [
                'item_schema_type'            => '__disabled',
                'item_schema_properties'      => [],
                'category_schema_type'        => '',
                'category_schema_properties'  => [],
            ],
        ],
    ));
    assertContactPluginSame('__disabled', $disabledPersistenceModel->saved[1]['params']['item_schema_type'] ?? null, 'Explicit contact-category-template disabling must be persisted independently.');

    $menuPersistenceModel = new ItemModel();
    $menuPersistenceModel->storedParamsByContext['com_menus.item.com_contact:201'] = [
        'schema_type'       => 'Person',
        'schema_properties' => ['name' => 'Menu contact'],
    ];
    $menuPersistencePlugin = new Contact([], $menuPersistenceModel);
    $menuPersistencePlugin->setApplication($administratorApplication);
    $menuDataEvent = new PrepareDataEvent('com_menus.item', (object) [
        'id'   => 201,
        'link' => 'index.php?option=com_contact&view=contact&id=7',
    ]);
    $menuPersistencePlugin->prepareData($menuDataEvent);
    assertContactPluginSame('Person', $menuDataEvent->getData()->microschema['schema_type'] ?? null, 'Stored contact menu settings must populate the menu form.');

    $menuPersistencePlugin->afterSave(new AfterSaveEvent(
        'com_menus.item',
        (object) ['id' => 202, 'link' => 'index.php?option=com_contact&view=category&id=3'],
        [
            'request' => ['option' => 'com_contact', 'view' => 'category'],
            'microschema' => [
                'item_schema_type'            => 'Person',
                'item_schema_properties'      => ['name' => '{contact.name}'],
                'category_schema_type'        => 'CollectionPage',
                'category_schema_properties'  => ['name' => '{category.title}'],
            ],
        ],
    ));
    assertContactPluginSame('com_menus.item.com_contact', $menuPersistenceModel->saved[0]['context'] ?? null, 'Contact menu settings must use their integration-owned storage context.');
    assertContactPluginSame('CollectionPage', $menuPersistenceModel->saved[0]['params']['category_schema_type'] ?? null, 'A contact category menu item must persist both override scopes.');

    $administratorApplication->jform = [
        'microschema' => [
            'item_schema_type'            => 'Person',
            'item_schema_properties'      => ['name' => '{contact.name}'],
            'category_schema_type'        => 'CollectionPage',
            'category_schema_properties'  => ['name' => '{category.title}'],
        ],
    ];
    $menuPersistencePlugin->afterSave(new AfterSaveEvent(
        'com_menus.item',
        (object) ['id' => 203, 'link' => 'index.php?option=com_contact&view=category&id=3'],
        null,
    ));
    assertContactPluginSame('CollectionPage', $menuPersistenceModel->saved[1]['params']['category_schema_type'] ?? null, 'Contact menu settings must fall back to the submitted form when the after-save event omits data.');
    $administratorApplication->jform = [];

    $menuPersistencePlugin->afterSave(new AfterSaveEvent(
        'com_menus.item',
        (object) ['id' => 204, 'link' => 'index.php?option=com_contact&view=featured'],
        [
            'request' => ['option' => 'com_contact', 'view' => 'featured'],
            'microschema' => [
                'page_schema_type'       => 'CollectionPage',
                'page_schema_properties' => ['name' => 'Featured contacts'],
                'item_schema_type'       => 'Person',
                'item_schema_properties' => ['name' => '{contact.name}'],
            ],
        ],
    ));
    assertContactPluginSame('CollectionPage', $menuPersistenceModel->saved[2]['params']['page_schema_type'] ?? null, 'A featured contacts menu item must persist its page schema.');
    assertContactPluginSame('Person', $menuPersistenceModel->saved[2]['params']['item_schema_type'] ?? null, 'A featured contacts menu item must persist its contact template.');

    $menuPersistencePlugin->afterSave(new AfterSaveEvent(
        'com_menus.item',
        (object) ['id' => 205, 'link' => 'index.php?option=com_contact&view=categories&id=1'],
        [
            'request' => ['option' => 'com_contact', 'view' => 'categories', 'id' => 1],
            'microschema' => [
                'page_schema_type'           => 'CollectionPage',
                'page_schema_properties'     => [],
                'category_schema_type'       => 'WebPage',
                'category_schema_properties' => [],
                'item_schema_type'           => 'Person',
            ],
        ],
    ));
    assertContactPluginSame(false, isset($menuPersistenceModel->saved[3]['params']['item_schema_type']), 'An all-contact-categories menu item must not persist a contact override.');

    $menuPersistencePlugin->afterDelete(new AfterDeleteEvent('com_menus.item', (object) ['id' => 202]));
    assertContactPluginSame(
        ['context' => 'com_menus.item.com_contact', 'itemId' => 202],
        $menuPersistenceModel->deleted[0] ?? null,
        'Deleting a menu item must delete its contact override settings.',
    );

    $sourcesEvent = new RegisterDataSourcesEvent();
    $collectionsEvent = new RegisterDataCollectionsEvent();
    $typesEvent   = new RegisterDataTypesEvent();
    $plugin->registerDataSources($sourcesEvent);
    $plugin->registerDataCollections($collectionsEvent);
    $plugin->registerDataTypes($typesEvent);

    assertContactPluginSame(ContactDataSource::class, $sourcesEvent->sources[0]::class, 'The contact source must be registered.');
    assertContactPluginSame(CategoryContactsCollection::class, $collectionsEvent->collections[0]::class, 'The contact category collection must be registered.');
    assertContactPluginSame(MenuContactsCollection::class, $collectionsEvent->collections[1]::class, 'The menu contact collection must be registered.');
    assertContactPluginSame(MenuCategoriesCollection::class, $collectionsEvent->collections[2]::class, 'The menu category collection must be registered.');
    assertContactPluginSame(ContactDataType::class, $typesEvent->types[0]::class, 'The contact type must be registered.');

    $contact = (object) [
        'id'           => 7,
        'name'         => 'Ada Lovelace',
        'user_id'      => 19,
        'catid'        => 3,
        'language'     => '*',
        'telephone'    => '+1 555 0100',
        'customFields' => [],
    ];
    $context = new DataContext('com_contact.contact', 7, $contact, ['event_room' => 'Hall A']);
    $source  = $sourcesEvent->sources[0];
    $value   = $source->getValue($context);
    $type    = $typesEvent->types[0];

    assertContactPluginSame('contact', $source->getName(), 'The contact source name must be stable.');
    assertContactPluginSame('JoomlaContact', $source->getType(), 'The contact source must reference its reusable type.');
    assertContactPluginSame(ContextualDataValue::class, $value::class, 'The contact source must return a contextual value.');
    assertContactPluginSame(['event_room' => 'Hall A'], $value->fieldValues, 'The contact source must retain submitted custom field values.');
    $contactPreviewContext = new DataContext('com_contact.category.contacts', 0, []);
    assertContactPluginSame(true, $source->supportsContext($contactPreviewContext->context), 'The contact source must be available while configuring a category template.');
    assertContactPluginSame('com_contact.contact', $source->getValue($contactPreviewContext)->context, 'The preview value must retain the runtime contact context.');

    $fieldsByName = [];

    foreach ($type->getFields($value, $context) as $field) {
        $fieldsByName[$field->name] = $field;
    }

    assertContactPluginSame('JoomlaUser', $fieldsByName['user']->type ?? null, 'The linked user must use the shared Joomla user type.');
    assertContactPluginSame('JoomlaCategory', $fieldsByName['category']->type ?? null, 'The category must use the shared Joomla category type.');
    assertContactPluginSame('JoomlaCustomFields', $fieldsByName['fields']->type ?? null, 'Custom fields must belong to the contact object.');
    assertContactPluginSame('Ada Lovelace', $type->resolve($value, 'name', $context), 'The contact type must resolve its own scalar fields.');
    assertContactPluginSame('https://example.test/contact/7', $type->resolve($value, 'link', $context), 'The contact type must resolve an absolute contact link.');

    $user = $type->resolve($value, 'user', $context);
    assertContactPluginSame('com_users.user', $user->context ?? null, 'The linked user must carry its own Joomla context.');
    assertContactPluginSame(19, $user->itemId ?? null, 'The linked user must retain its user id.');
    assertContactPluginSame($value, $type->resolve($value, 'fields', $context), 'Contact custom fields must retain the contact context.');

    $registryContact = new Registry(['name' => 'Registry contact']);
    $registryValue = new ContextualDataValue('com_contact.contact', 7, $registryContact);
    assertContactPluginSame('Registry contact', $type->resolve($registryValue, 'name', $context), 'Registry contact fields must resolve through Registry::get().');
    assertContactPluginSame(null, $type->resolve($registryValue, 'user', $context), 'A missing Registry contact user must resolve to null.');
    assertContactPluginSame(null, $type->resolve($registryValue, 'category', $context), 'A missing Registry contact category must resolve to null.');
    assertContactPluginSame(0, $registryContact->magicReads, 'Contact resolution must not invoke Registry::__get().');

    Categories::$category = (object) ['id' => 3, 'title' => 'Team', 'language' => '*'];
    $category = $type->resolve($value, 'category', $context);
    assertContactPluginSame('com_contact.categories', $category->context ?? null, 'The contact category must carry the universal category context.');
    assertContactPluginSame('https://example.test/contact-category/3', $category->overrides['link'] ?? null, 'The contact category must provide its integration-specific link.');

    $unsupported = new DataContext('com_content.article', 7, (object) ['id' => 7]);
    assertContactPluginSame(false, $source->supportsContext($unsupported->context), 'The contact source context must be strict.');
    assertContactPluginSame(null, $source->getValue($unsupported), 'Unsupported contexts must not expose the contact root.');

    $runtimeModel = new ItemModel();
    $runtimeModel->storedParamsByContext['com_contact.categories:3'] = [
        'item_schema_type'       => 'Person',
        'item_schema_properties' => ['name' => 'Static contact name'],
    ];
    $runtimeContact = (object) [
        'id'     => 7,
        'catid'  => 3,
        'name'   => 'Runtime contact',
        'title'  => 'Runtime contact',
        'params' => new class () {
            public function get(string $name, mixed $default = null): mixed
            {
                return $name === 'access-view' ? true : $default;
            }
        },
    ];
    $contactRootCategory = new class () {
        public int $id = 1;

        public function getParent(): ?object
        {
            return null;
        }
    };
    $contactParentCategory = new class ($contactRootCategory) {
        public int $id = 2;

        public function __construct(private readonly object $parent)
        {
        }

        public function getParent(): object
        {
            return $this->parent;
        }
    };
    $contactChildCategory = new class ($contactParentCategory) {
        public int $id = 3;

        public function __construct(private readonly object $parent)
        {
        }

        public function getParent(): object
        {
            return $this->parent;
        }
    };
    Categories::$items = [1 => $contactRootCategory, 2 => $contactParentCategory, 3 => $contactChildCategory];
    $runtimeApplication = new class ($runtimeContact) implements CMSApplicationInterface {
        public ?object $activeMenuItem = null;

        public function __construct(private readonly object $contact)
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
                        'option' => 'com_contact',
                        'view'   => 'contact',
                        default  => '',
                    };
                }

                public function getInt(string $name): int
                {
                    return $name === 'id' ? 7 : 0;
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

            $contact = $this->contact;

            return new class ($contact) {
                public function __construct(private readonly object $contact)
                {
                }

                public function getMVCFactory(): object
                {
                    $contact = $this->contact;

                    return new class ($contact) {
                        public function __construct(private readonly object $contact)
                        {
                        }

                        public function createModel(string $name, string $client, array $options): object
                        {
                            $contact = $this->contact;

                            return new class ($contact) {
                                public function __construct(private readonly object $contact)
                                {
                                }

                                public function getItem(int $itemId): object
                                {
                                    return $this->contact;
                                }
                            };
                        }
                    };
                }
            };
        }
    };
    $runtimePlugin = new Contact([
        'params' => [
            'default_item_schema_type'       => 'Organization',
            'default_item_schema_properties' => ['name' => 'Global contact'],
        ],
    ], $runtimeModel);
    $runtimePlugin->setApplication($runtimeApplication);
    $collectEvent = new CollectSchemasEvent();
    $runtimePlugin->collectSchemas($collectEvent);

    assertContactPluginSame(
        [['context' => 'com_contact.contact', 'id' => 7]],
        $collectEvent->contexts,
        'The current contact must be appended to the schema context path.',
    );
    assertContactPluginSame(
        'Static contact name',
        $collectEvent->schemas[0]['data']['name'] ?? null,
        'The configured contact schema must be registered.',
    );

    $directRuntimeModel = new ItemModel();
    $directRuntimeModel->storedParamsByContext = [
        'com_contact.contact:7' => [
            'schema_type'       => 'Person',
            'schema_properties' => ['name' => 'Individual contact'],
        ],
        'com_contact.categories:3' => [
            'item_schema_type'       => '__disabled',
            'item_schema_properties' => [],
        ],
    ];
    $directRuntimePlugin = new Contact([
        'params' => [
            'default_item_schema_type'       => 'Organization',
            'default_item_schema_properties' => ['name' => 'Global contact'],
        ],
    ], $directRuntimeModel);
    $directRuntimePlugin->setApplication($runtimeApplication);
    $directCollectEvent = new CollectSchemasEvent();
    $directRuntimePlugin->collectSchemas($directCollectEvent);
    assertContactPluginSame('Person', $directCollectEvent->schemas[0]['data']['@type'] ?? null, 'Individual contact settings must fully replace the category template.');
    assertContactPluginSame('Individual contact', $directCollectEvent->schemas[0]['data']['name'] ?? null, 'Category values must not leak into an individual contact schema.');

    $runtimeApplication->activeMenuItem = (object) [
        'id'    => 200,
        'query' => ['option' => 'com_contact', 'view' => 'category', 'id' => 3],
    ];
    $menuOverrideModel = new ItemModel();
    $menuOverrideModel->storedParamsByContext = [
        'com_contact.contact:7' => [
            'schema_type'       => 'Person',
            'schema_properties' => ['name' => 'Individual contact'],
        ],
        'com_menus.item.com_contact:200' => [
            'item_schema_type'       => 'Organization',
            'item_schema_properties' => ['name' => 'Category menu contact'],
        ],
    ];
    $menuOverridePlugin = new Contact([], $menuOverrideModel);
    $menuOverridePlugin->setApplication($runtimeApplication);
    $menuOverrideEvent = new CollectSchemasEvent();
    $menuOverridePlugin->collectSchemas($menuOverrideEvent);
    assertContactPluginSame('Category menu contact', $menuOverrideEvent->schemas[0]['data']['name'] ?? null, 'The active category menu contact template must override individual contact settings.');
    assertContactPluginSame('com_menus.item:200', $menuOverrideEvent->schemas[0]['contextKey'] ?? null, 'A contact menu override must be registered after the current contact context.');

    $menuOverrideModel->storedParamsByContext['com_menus.item.com_contact:200']['item_schema_type'] = '__disabled';
    $disabledMenuEvent = new CollectSchemasEvent();
    $menuOverridePlugin->collectSchemas($disabledMenuEvent);
    assertContactPluginSame([], $disabledMenuEvent->schemas, 'A disabled active menu override must stop contact, category, and global fallback.');

    $runtimeApplication->activeMenuItem = (object) [
        'id'    => 206,
        'query' => ['option' => 'com_contact', 'view' => 'featured'],
    ];
    $menuOverrideModel->storedParamsByContext['com_menus.item.com_contact:206'] = [
        'item_schema_type'       => 'Person',
        'item_schema_properties' => ['name' => 'Featured menu contact'],
    ];
    $featuredChildEvent = new CollectSchemasEvent();
    $menuOverridePlugin->collectSchemas($featuredChildEvent);
    assertContactPluginSame('Featured menu contact', $featuredChildEvent->schemas[0]['data']['name'] ?? null, 'A featured menu contact template must override individual contact settings.');
    $runtimeApplication->activeMenuItem = null;

    $parentRuntimeModel = new ItemModel();
    $parentRuntimeModel->storedParamsByContext['com_contact.categories:2'] = [
        'item_schema_type'       => 'Person',
        'item_schema_properties' => ['name' => 'Inherited parent contact'],
    ];
    $parentRuntimePlugin = new Contact([
        'params' => [
            'default_item_schema_type'       => 'Organization',
            'default_item_schema_properties' => ['name' => 'Global contact'],
        ],
    ], $parentRuntimeModel);
    $parentRuntimePlugin->setApplication($runtimeApplication);
    $parentCollectEvent = new CollectSchemasEvent();
    $parentRuntimePlugin->collectSchemas($parentCollectEvent);
    assertContactPluginSame('Inherited parent contact', $parentCollectEvent->schemas[0]['data']['name'] ?? null, 'Default contact settings must continue through parent categories before the global template.');

    $disabledRuntimeModel = new ItemModel();
    $disabledRuntimeModel->storedParamsByContext['com_contact.categories:3'] = [
        'item_schema_type'       => '__disabled',
        'item_schema_properties' => [],
    ];
    $disabledRuntimePlugin = new Contact([
        'params' => [
            'default_item_schema_type'       => 'Organization',
            'default_item_schema_properties' => ['name' => 'Global contact'],
        ],
    ], $disabledRuntimeModel);
    $disabledRuntimePlugin->setApplication($runtimeApplication);
    $disabledCollectEvent = new CollectSchemasEvent();
    $disabledRuntimePlugin->collectSchemas($disabledCollectEvent);
    assertContactPluginSame([], $disabledCollectEvent->schemas, 'Disabled category markup must stop contact inheritance before the global template.');

    $disabledContactModel = new ItemModel();
    $disabledContactModel->storedParamsByContext['com_contact.contact:7'] = [
        'schema_type'       => '__disabled',
        'schema_properties' => [],
    ];
    $disabledContactModel->storedParamsByContext['com_contact.categories:3'] = [
        'item_schema_type'       => 'Person',
        'item_schema_properties' => ['name' => 'Category contact'],
    ];
    $disabledContactPlugin = new Contact([
        'params' => [
            'default_item_schema_type'       => 'Organization',
            'default_item_schema_properties' => ['name' => 'Global contact'],
        ],
    ], $disabledContactModel);
    $disabledContactPlugin->setApplication($runtimeApplication);
    $disabledContactEvent = new CollectSchemasEvent();
    $disabledContactPlugin->collectSchemas($disabledContactEvent);
    assertContactPluginSame([], $disabledContactEvent->schemas, 'Disabled contact markup must stop category and global inheritance.');

    $runtimeCategoryModel = new ItemModel();
    $runtimeCategoryModel->storedParamsByContext['com_contact.categories:3'] = [
        'category_schema_type'       => 'CollectionPage',
        'category_schema_properties' => ['name' => '{category.title}'],
    ];
    $runtimeCategory = (object) ['id' => 3, 'title' => 'Team', 'language' => '*'];
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
                        'option' => 'com_contact',
                        'view'   => 'category',
                        default  => '',
                    };
                }

                public function getInt(string $name): int
                {
                    return $name === 'id' ? 3 : 0;
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
                                    return [(object) ['id' => 31, 'name' => 'First category contact']];
                                }

                                public function getPagination(): object
                                {
                                    return (object) ['limitstart' => 10, 'total' => 16];
                                }
                            };
                        }
                    };
                }
            };
        }
    };
    $runtimeCategoryPlugin = new Contact([
        'params' => [
            'default_category_schema_type'       => 'WebPage',
            'default_category_schema_properties' => ['name' => 'Global category'],
        ],
    ], $runtimeCategoryModel);
    $runtimeCategoryPlugin->setApplication($runtimeCategoryApplication);
    $categoryCollectEvent = new CollectSchemasEvent();
    $runtimeCategoryPlugin->collectSchemas($categoryCollectEvent);
    assertContactPluginSame('Team', $categoryCollectEvent->schemas[0]['data']['name'] ?? null, 'The contact category page must resolve its own category source.');
    assertContactPluginSame('com_contact.categories:3', $categoryCollectEvent->schemas[0]['contextKey'] ?? null, 'The contact category schema must be registered under its canonical context.');

    $runtimeCategoryApplication->activeMenuItem = (object) [
        'id'    => 203,
        'query' => ['option' => 'com_contact', 'view' => 'category', 'id' => 3],
    ];
    $menuCategoryModel = new ItemModel();
    $menuCategoryModel->storedParamsByContext = [
        'com_contact.categories:3' => [
            'category_schema_type'       => 'CollectionPage',
            'category_schema_properties' => ['name' => 'Contact category entity'],
        ],
        'com_menus.item.com_contact:203' => [
            'category_schema_type'       => 'WebPage',
            'category_schema_properties' => ['name' => 'Contact category menu page'],
        ],
    ];
    $menuCategoryPlugin = new Contact([], $menuCategoryModel);
    $menuCategoryPlugin->setApplication($runtimeCategoryApplication);
    $menuCategoryEvent = new CollectSchemasEvent();
    $menuCategoryPlugin->collectSchemas($menuCategoryEvent);
    assertContactPluginSame('Contact category menu page', $menuCategoryEvent->schemas[0]['data']['name'] ?? null, 'The active contact category menu page settings must override category inheritance.');
    assertContactPluginSame('com_menus.item:203', $menuCategoryEvent->schemas[0]['contextKey'] ?? null, 'A contact category page menu override must be the most specific context.');
    $runtimeCategoryApplication->activeMenuItem = null;

    $parentCategoryPageModel = new ItemModel();
    $parentCategoryPageModel->storedParamsByContext['com_contact.categories:2'] = [
        'category_schema_type'       => 'CollectionPage',
        'category_schema_properties' => ['name' => 'Inherited contact category page'],
    ];
    $parentCategoryPagePlugin = new Contact([
        'params' => [
            'default_category_schema_type'       => 'WebPage',
            'default_category_schema_properties' => ['name' => 'Global category'],
        ],
    ], $parentCategoryPageModel);
    $parentCategoryPagePlugin->setApplication($runtimeCategoryApplication);
    $parentCategoryPageEvent = new CollectSchemasEvent();
    $parentCategoryPagePlugin->collectSchemas($parentCategoryPageEvent);
    assertContactPluginSame('Inherited contact category page', $parentCategoryPageEvent->schemas[0]['data']['name'] ?? null, 'Contact category pages must inherit markup through their parent categories.');

    $disabledCategoryPageModel = new ItemModel();
    $disabledCategoryPageModel->storedParamsByContext['com_contact.categories:3'] = [
        'category_schema_type'       => '__disabled',
        'category_schema_properties' => [],
    ];
    $disabledCategoryPagePlugin = new Contact([
        'params' => [
            'default_category_schema_type'       => 'WebPage',
            'default_category_schema_properties' => ['name' => 'Global category'],
        ],
    ], $disabledCategoryPageModel);
    $disabledCategoryPagePlugin->setApplication($runtimeCategoryApplication);
    $disabledCategoryPageEvent = new CollectSchemasEvent();
    $disabledCategoryPagePlugin->collectSchemas($disabledCategoryPageEvent);
    assertContactPluginSame([], $disabledCategoryPageEvent->schemas, 'Disabled contact category-page markup must stop parent and global inheritance.');

    $globalContactPlugin = new Contact([
        'params' => [
            'default_item_schema_type'       => 'Person',
            'default_item_schema_properties' => ['name' => '{contact.name}'],
        ],
    ], new ItemModel());
    $globalContactPlugin->setApplication($runtimeApplication);
    $globalContactEvent = new CollectSchemasEvent();
    $globalContactPlugin->collectSchemas($globalContactEvent);
    assertContactPluginSame('Person', $globalContactEvent->schemas[0]['data']['@type'] ?? null, 'The global contact template must be the final item fallback.');
    assertContactPluginSame('Runtime contact', $globalContactEvent->schemas[0]['data']['name'] ?? null, 'Global contact placeholders must resolve against the current contact.');

    $globalCategoryPlugin = new Contact([
        'params' => [
            'default_category_schema_type'       => 'CollectionPage',
            'default_category_schema_properties' => ['name' => '{category.title}'],
        ],
    ], new ItemModel());
    $globalCategoryPlugin->setApplication($runtimeCategoryApplication);
    $globalCategoryEvent = new CollectSchemasEvent();
    $globalCategoryPlugin->collectSchemas($globalCategoryEvent);
    assertContactPluginSame('Team', $globalCategoryEvent->schemas[0]['data']['name'] ?? null, 'The global contact category template must resolve against the current category.');
    $runtimeCollectionsEvent = new RegisterDataCollectionsEvent();
    $runtimeCategoryPlugin->registerDataCollections($runtimeCollectionsEvent);
    $runtimeCollectionResult = $runtimeCollectionsEvent->collections[0]->getItems(
        new DataContext('com_contact.categories', 3, $runtimeCategory),
    );
    assertContactPluginSame('com_contact.contact', $runtimeCollectionResult->items[0]->context ?? null, 'The contacts collection must return contact data contexts.');
    assertContactPluginSame(10, $runtimeCollectionResult->offset, 'The contacts collection must preserve the current pagination offset.');
    assertContactPluginSame(16, $runtimeCollectionResult->total, 'The contacts collection must expose the category total.');

    $menuPageApplication = new class () implements CMSApplicationInterface {
        private object $active;

        public function __construct()
        {
            $this->active = (object) [
                'id'    => 210,
                'title' => 'Featured contacts',
                'query' => ['option' => 'com_contact', 'view' => 'featured'],
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
                    return $name === 'option' ? 'com_contact' : ($name === 'view' ? 'featured' : '');
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
    $menuPageModel->storedParamsByContext['com_menus.item.com_contact:210'] = [
        'page_schema_type'       => 'CollectionPage',
        'page_schema_properties' => ['name' => 'Featured contacts page'],
    ];
    $menuPagePlugin = new Contact([], $menuPageModel);
    $menuPagePlugin->setApplication($menuPageApplication);
    $menuPageEvent = new CollectSchemasEvent();
    $menuPagePlugin->collectSchemas($menuPageEvent);
    assertContactPluginSame('Featured contacts page', $menuPageEvent->schemas[0]['data']['name'] ?? null, 'An explicit featured contacts page schema must be collected.');
    assertContactPluginSame('com_menus.item:210', $menuPageEvent->schemas[0]['contextKey'] ?? null, 'A contacts menu page schema must use the menu override context.');

    echo "Contact plugin tests passed.\n";
}
