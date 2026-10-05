<?php

/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Microschema\Contact\Extension;

defined('_JEXEC') || exit;

use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Event\Model\AfterDeleteEvent;
use Joomla\CMS\Event\Model\AfterSaveEvent;
use Joomla\CMS\Event\Model\PrepareDataEvent;
use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Component\Contact\Site\Helper\RouteHelper;
use Joomla\Component\Microschema\Administrator\DataSource\ContextualDataValue;
use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
use Joomla\Component\Microschema\Administrator\Event\CollectSchemasEvent;
use Joomla\Component\Microschema\Administrator\Event\RegisterDataCollectionsEvent;
use Joomla\Component\Microschema\Administrator\Event\RegisterDataSourcesEvent;
use Joomla\Component\Microschema\Administrator\Event\RegisterDataTypesEvent;
use Joomla\Component\Microschema\Administrator\Extension\MicroschemaComponent;
use Joomla\Component\Microschema\Administrator\Model\ItemModel;
use Joomla\Component\Microschema\Administrator\Schema\SchemaDataBuilder;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Microschema\Contact\DataCollection\CategoryContactsCollection;
use Joomla\Plugin\Microschema\Contact\DataCollection\MenuCategoriesCollection;
use Joomla\Plugin\Microschema\Contact\DataCollection\MenuContactsCollection;
use Joomla\Plugin\Microschema\Contact\DataSource\ContactDataSource;
use Joomla\Plugin\Microschema\Contact\DataType\ContactDataType;
use Joomla\Registry\Registry;

final class Contact extends CMSPlugin implements SubscriberInterface, DispatcherAwareInterface
{
    use DispatcherAwareTrait;

    private const CONTACT_CONTEXT = 'com_contact.contact';

    private const CATEGORY_CONTEXT = 'com_contact.categories';

    private const FEATURED_CONTEXT = 'com_contact.featured';

    private const MENU_CATEGORIES_CONTEXT = 'com_contact.menu.categories';

    private const CATEGORY_EVENT_CONTEXT = 'com_categories.category';

    private const CATEGORY_EXTENSION = 'com_contact';

    private const MENU_EVENT_CONTEXT = 'com_menus.item';

    private const MENU_STORAGE_CONTEXT = 'com_menus.item.com_contact';

    private const SCHEMA_DISABLED = '__disabled';

    protected $autoloadLanguage = true;

    public function __construct(array $config, private readonly ItemModel $itemModel)
    {
        parent::__construct($config);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepareData' => 'prepareData',
            'onContentPrepareForm' => 'prepareForm',
            'onContentAfterSave' => 'afterSave',
            'onContentAfterDelete' => 'afterDelete',
            RegisterDataSourcesEvent::NAME => 'registerDataSources',
            RegisterDataCollectionsEvent::NAME => 'registerDataCollections',
            RegisterDataTypesEvent::NAME => 'registerDataTypes',
            CollectSchemasEvent::NAME => 'collectSchemas',
        ];
    }

    public function collectSchemas(CollectSchemasEvent $event): void
    {
        $application = $this->getApplication();
        $input = $application->getInput();

        if (!$application->isClient('site') || $input->getCmd('option') !== 'com_contact') {
            return;
        }

        match ($input->getCmd('view')) {
            'contact' => $this->collectContactSchema($event),
            'category' => $this->collectCategorySchema($event),
            'featured' => $this->collectMenuPageSchema($event, 'featured', self::FEATURED_CONTEXT),
            'categories' => $this->collectMenuPageSchema($event, 'categories', self::MENU_CATEGORIES_CONTEXT),
            default => null,
        };
    }

    private function collectMenuPageSchema(CollectSchemasEvent $event, string $target, string $context): void
    {
        $application = $this->getApplication();

        if (!method_exists($application, 'getMenu')) {
            return;
        }

        $menu = $application->getMenu();
        $active = is_object($menu) && method_exists($menu, 'getActive') ? $menu->getActive() : null;

        if (!is_object($active) || $this->getMenuTarget($active) !== $target) {
            return;
        }

        $menuItemId = $this->getItemId($active);
        $settings = $this->itemModel->getParamsByContext(self::MENU_STORAGE_CONTEXT, $menuItemId);
        $schemaType = trim((string) ($settings['page_schema_type'] ?? ''));

        if ($menuItemId < 1 || $schemaType === '' || $schemaType === self::SCHEMA_DISABLED) {
            return;
        }

        $item = $active;

        if ($target === 'categories') {
            $query = $this->getMenuQuery($active);
            $categoryId = (int) ($query['id'] ?? 0);
            $categories = Categories::getInstance('contact', ['countItems' => false]);
            $category = is_object($categories) && $categoryId > 0 ? $categories->get($categoryId) : null;

            if (is_object($category)) {
                $item = new ContextualDataValue($context, $categoryId, $category);
            }
        }

        $component = $application->bootComponent('com_microschema');

        if (!$component instanceof MicroschemaComponent) {
            return;
        }

        $properties = $component->getDataSourceTemplateResolver()->resolve(
            $settings['page_schema_properties'] ?? [],
            new DataContext($context, $menuItemId, $item),
        );
        $builder = new SchemaDataBuilder(array_keys($component->getMetadataRegistry()->getSchemaOrg()));
        $schema = $builder->build($schemaType, $properties);

        if (count($schema) < 2) {
            return;
        }

        $contextKey = $context.':'.$menuItemId;

        if (!$event->getPath()->has($contextKey)) {
            $event->appendContext($context, $menuItemId);
        }

        $contextKey = $event->addMenuOverride($contextKey, $menuItemId)->getKey();
        $event->addSchema($contextKey, 'primary', $schema);
    }

    private function collectContactSchema(CollectSchemasEvent $event): void
    {
        $application = $this->getApplication();
        $input = $application->getInput();
        $itemId = $input->getInt('id');

        if ($itemId < 1) {
            return;
        }

        $contactComponent = $application->bootComponent('com_contact');
        $contactModel = $contactComponent->getMVCFactory()->createModel(
            'Contact',
            'Site',
            [],
        );

        if (!is_object($contactModel) || !method_exists($contactModel, 'getItem')) {
            return;
        }

        $contact = $contactModel->getItem($itemId);

        if (
            !is_object($contact)
            || !isset($contact->params)
            || !method_exists($contact->params, 'get')
            || !(bool) $contact->params->get('access-view', false)
        ) {
            return;
        }

        $menuOverride = $this->getActiveMenuSchemaSettings('item');
        $settings = $menuOverride['settings']
            ?? $this->itemModel->getParamsByContext(self::CONTACT_CONTEXT, $itemId);

        if ($this->isSchemaDisabled($settings)) {
            return;
        }

        if (trim((string) ($settings['schema_type'] ?? '')) === '') {
            $settings = $this->getInheritedCategorySchemaSettings((int) ($contact->catid ?? 0), 'item')
                ?? $this->getDefaultSchemaSettings('default_item');
        }

        if ($this->isSchemaDisabled($settings)) {
            return;
        }

        $schemaType = trim((string) ($settings['schema_type'] ?? ''));

        if ($schemaType === '') {
            return;
        }

        $dataContext = new DataContext(
            context: self::CONTACT_CONTEXT,
            itemId: $itemId,
            item: $contact,
        );
        $component = $application->bootComponent('com_microschema');

        if (!$component instanceof MicroschemaComponent) {
            return;
        }

        $properties = $component->getDataSourceTemplateResolver()->resolve(
            $settings['schema_properties'] ?? [],
            $dataContext,
        );
        $builder = new SchemaDataBuilder(array_keys($component->getMetadataRegistry()->getSchemaOrg()));
        $schema = $builder->build($schemaType, $properties);

        if (count($schema) < 2) {
            return;
        }

        $contextKey = self::CONTACT_CONTEXT.':'.$itemId;

        if (!$event->getPath()->has($contextKey)) {
            $event->appendContext(self::CONTACT_CONTEXT, $itemId);
        }

        if ($menuOverride !== null) {
            $contextKey = $event->addMenuOverride($contextKey, $menuOverride['menuItemId'])->getKey();
        }

        $event->addSchema($contextKey, 'primary', $schema);
    }

    private function collectCategorySchema(CollectSchemasEvent $event): void
    {
        $application = $this->getApplication();
        $itemId = $application->getInput()->getInt('id');

        if ($itemId < 1) {
            return;
        }

        $menuOverride = $this->getActiveMenuSchemaSettings('category');
        $settings = $menuOverride['settings']
            ?? $this->getInheritedCategorySchemaSettings($itemId, 'category')
            ?? $this->getDefaultSchemaSettings('default_category');

        if ($this->isSchemaDisabled($settings)) {
            return;
        }

        $schemaType = trim((string) ($settings['schema_type'] ?? ''));

        if ($schemaType === '') {
            return;
        }

        $categoryModel = $application->bootComponent('com_contact')->getMVCFactory()->createModel(
            'Category',
            'Site',
            [],
        );

        if (!is_object($categoryModel) || !method_exists($categoryModel, 'getCategory')) {
            return;
        }

        $category = $categoryModel->getCategory();

        if (!is_object($category) || (int) ($category->id ?? 0) !== $itemId) {
            return;
        }

        $language = (string) ($category->language ?? '*');
        $value = new ContextualDataValue(
            self::CATEGORY_CONTEXT,
            $itemId,
            $category,
            overrides: [
                'link' => Route::_(RouteHelper::getCategoryRoute($itemId, $language), true, 0, true),
            ],
        );
        $dataContext = new DataContext(self::CATEGORY_CONTEXT, $itemId, $value);
        $component = $application->bootComponent('com_microschema');

        if (!$component instanceof MicroschemaComponent) {
            return;
        }

        $properties = $component->getDataSourceTemplateResolver()->resolve(
            $settings['schema_properties'] ?? [],
            $dataContext,
        );
        $builder = new SchemaDataBuilder(array_keys($component->getMetadataRegistry()->getSchemaOrg()));
        $schema = $builder->build($schemaType, $properties);

        if (count($schema) < 2) {
            return;
        }

        $contextKey = self::CATEGORY_CONTEXT.':'.$itemId;

        if (!$event->getPath()->has($contextKey)) {
            $event->appendContext(self::CATEGORY_CONTEXT, $itemId);
        }

        if ($menuOverride !== null) {
            $contextKey = $event->addMenuOverride($contextKey, $menuOverride['menuItemId'])->getKey();
        }

        $event->addSchema($contextKey, 'primary', $schema);
    }

    public function prepareData(PrepareDataEvent $event): void
    {
        if (!$this->getApplication()->isClient('administrator')) {
            return;
        }

        $data = $event->getData();

        if ($event->getContext() === self::MENU_EVENT_CONTEXT) {
            $this->prepareMenuData($event, $data);

            return;
        }

        if ($event->getContext() === self::CATEGORY_EVENT_CONTEXT) {
            $this->prepareCategoryData($event, $data);

            return;
        }

        if ($event->getContext() !== self::CONTACT_CONTEXT) {
            return;
        }

        $settings = $this->getMicroschemaData($data);

        if (array_key_exists('schema_type', $settings) && array_key_exists('schema_properties', $settings)) {
            return;
        }

        $itemId = $this->getItemId($data);

        if ($itemId < 1) {
            return;
        }

        $storedSettings = $this->itemModel->getParamsByContext(self::CONTACT_CONTEXT, $itemId);

        if ($storedSettings === []) {
            return;
        }

        $settings = array_replace($storedSettings, $settings);

        if (is_array($data)) {
            $data['microschema'] = $settings;
        } elseif (method_exists($data, 'set')) {
            $data->set('microschema', $settings);
        } else {
            $data->microschema = $settings;
        }

        $event->updateData($data);
    }

    public function afterSave(AfterSaveEvent $event): void
    {
        if (!$this->getApplication()->isClient('administrator')) {
            return;
        }

        if ($event->getContext() === self::MENU_EVENT_CONTEXT) {
            $this->saveMenuData($event);

            return;
        }

        if ($event->getContext() === self::CATEGORY_EVENT_CONTEXT) {
            $this->saveCategoryData($event);

            return;
        }

        if ($event->getContext() !== self::CONTACT_CONTEXT) {
            return;
        }

        $settings = $this->getMicroschemaData($event->getData());

        if (!array_key_exists('schema_type', $settings) && !array_key_exists('schema_properties', $settings)) {
            return;
        }

        $itemId = (int) ($event->getItem()->id ?? 0);

        if ($itemId < 1) {
            return;
        }

        $schemaType = trim((string) ($settings['schema_type'] ?? ''));

        if ($schemaType === '') {
            if (!$this->itemModel->deleteItemByContext(self::CONTACT_CONTEXT, $itemId)) {
                throw new \RuntimeException('Unable to delete the MicroSchema contact settings.');
            }

            return;
        }

        $schemaProperties = $settings['schema_properties'] ?? [];

        if (!is_array($schemaProperties)) {
            $schemaProperties = [];
        }

        if (!$this->itemModel->saveParamsByContext(self::CONTACT_CONTEXT, $itemId, [
            'schema_type' => $schemaType,
            'schema_properties' => $schemaProperties,
        ])) {
            throw new \RuntimeException('Unable to save the MicroSchema contact settings.');
        }
    }

    public function afterDelete(AfterDeleteEvent $event): void
    {
        if ($event->getContext() === self::MENU_EVENT_CONTEXT) {
            $itemId = (int) ($event->getItem()->id ?? 0);

            if ($itemId > 0 && !$this->itemModel->deleteItemByContext(self::MENU_STORAGE_CONTEXT, $itemId)) {
                throw new \RuntimeException('Unable to delete the MicroSchema contact menu settings.');
            }

            return;
        }

        if ($event->getContext() === self::CATEGORY_EVENT_CONTEXT) {
            if (!$this->isOwnCategory($event->getItem())) {
                return;
            }

            $itemId = (int) ($event->getItem()->id ?? 0);

            if ($itemId > 0 && !$this->itemModel->deleteItemByContext(self::CATEGORY_CONTEXT, $itemId)) {
                throw new \RuntimeException('Unable to delete the MicroSchema contact category settings.');
            }

            return;
        }

        if ($event->getContext() !== self::CONTACT_CONTEXT) {
            return;
        }

        $itemId = (int) ($event->getItem()->id ?? 0);

        if ($itemId < 1) {
            return;
        }

        if (!$this->itemModel->deleteItemByContext(self::CONTACT_CONTEXT, $itemId)) {
            throw new \RuntimeException('Unable to delete the MicroSchema contact settings.');
        }
    }

    public function prepareForm(PrepareFormEvent $event): void
    {
        if (!$this->getApplication()->isClient('administrator')) {
            return;
        }

        $form = $event->getForm();

        if ($form->getName() === self::MENU_EVENT_CONTEXT) {
            $data = $event->getData();
            $target = $this->getMenuTarget(is_array($data) || is_object($data) ? $data : []);

            if ($target === null) {
                $application = $this->getApplication();
                $input = method_exists($application, 'getInput') ? $application->getInput() : null;
                $submitted = is_object($input) && method_exists($input, 'get')
                    ? $input->get('jform', [], 'array')
                    : [];
                $target = $this->getMenuTarget(is_array($submitted) ? $submitted : []);
            }

            $file = match ($target) {
                'contact' => 'menu_contact.xml',
                'category' => 'menu_category.xml',
                'featured' => 'menu_featured.xml',
                'categories' => 'menu_categories.xml',
                default => null,
            };

            if ($file !== null && !$form->loadFile(dirname(__DIR__, 2).'/forms/'.$file)) {
                throw new \RuntimeException('Unable to load the MicroSchema contact menu form.');
            }

            return;
        }

        if ($form->getName() === self::CONTACT_CONTEXT) {
            if (!$form->loadFile(dirname(__DIR__, 2).'/forms/contact.xml')) {
                throw new \RuntimeException('Unable to load the MicroSchema contact form.');
            }

            return;
        }

        if ($form->getName() === 'com_categories.categorycom_contact'
            && !$form->loadFile(dirname(__DIR__, 2).'/forms/category.xml')) {
            throw new \RuntimeException('Unable to load the MicroSchema contact category form.');
        }
    }

    public function registerDataSources(RegisterDataSourcesEvent $event): void
    {
        $event->register(new ContactDataSource());
    }

    public function registerDataCollections(RegisterDataCollectionsEvent $event): void
    {
        $event->register(new CategoryContactsCollection($this->getApplication()));
        $event->register(new MenuContactsCollection($this->getApplication()));
        $event->register(new MenuCategoriesCollection($this->getApplication()));
    }

    public function registerDataTypes(RegisterDataTypesEvent $event): void
    {
        $event->register(new ContactDataType());
    }

    /** @return array<string, mixed> */
    private function getMicroschemaData(object|array $data): array
    {
        if (is_array($data)) {
            $settings = $data['microschema'] ?? [];
        } elseif (method_exists($data, 'get')) {
            $settings = $data->get('microschema', []);
        } else {
            $settings = $data->microschema ?? [];
        }

        return is_array($settings) ? $settings : [];
    }

    private function prepareCategoryData(PrepareDataEvent $event, object|array $data): void
    {
        if (!$this->isOwnCategory($data)) {
            return;
        }

        $settings = $this->getMicroschemaData($data);

        if ($this->hasCategorySettings($settings)) {
            return;
        }

        $itemId = $this->getItemId($data);

        if ($itemId < 1) {
            return;
        }

        $storedSettings = $this->itemModel->getParamsByContext(self::CATEGORY_CONTEXT, $itemId);

        if ($storedSettings === []) {
            return;
        }

        $settings = array_replace($storedSettings, $settings);

        if (is_array($data)) {
            $data['microschema'] = $settings;
        } elseif (method_exists($data, 'set')) {
            $data->set('microschema', $settings);
        } else {
            $data->microschema = $settings;
        }

        $event->updateData($data);
    }

    private function saveCategoryData(AfterSaveEvent $event): void
    {
        if (!$this->isOwnCategory($event->getItem()) && !$this->isOwnCategory($event->getData())) {
            return;
        }

        $settings = $this->getMicroschemaData($event->getData());

        if (!$this->hasAnyCategorySetting($settings)) {
            return;
        }

        $itemId = (int) ($event->getItem()->id ?? 0);

        if ($itemId < 1) {
            return;
        }

        $settings = $this->normaliseCategorySettings($settings);

        if ($settings['item_schema_type'] === '' && $settings['category_schema_type'] === '') {
            if (!$this->itemModel->deleteItemByContext(self::CATEGORY_CONTEXT, $itemId)) {
                throw new \RuntimeException('Unable to delete the MicroSchema contact category settings.');
            }

            return;
        }

        if (!$this->itemModel->saveParamsByContext(self::CATEGORY_CONTEXT, $itemId, $settings)) {
            throw new \RuntimeException('Unable to save the MicroSchema contact category settings.');
        }
    }

    /** @param array<string, mixed> $settings */
    private function hasCategorySettings(array $settings): bool
    {
        return array_key_exists('item_schema_type', $settings)
            && array_key_exists('item_schema_properties', $settings)
            && array_key_exists('category_schema_type', $settings)
            && array_key_exists('category_schema_properties', $settings);
    }

    /** @param array<string, mixed> $settings */
    private function hasAnyCategorySetting(array $settings): bool
    {
        return array_intersect([
            'item_schema_type',
            'item_schema_properties',
            'category_schema_type',
            'category_schema_properties',
        ], array_keys($settings)) !== [];
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return array<string, mixed>
     */
    private function normaliseCategorySettings(array $settings): array
    {
        return [
            'item_schema_type' => trim((string) ($settings['item_schema_type'] ?? '')),
            'item_schema_properties' => is_array($settings['item_schema_properties'] ?? null)
                ? $settings['item_schema_properties']
                : [],
            'category_schema_type' => trim((string) ($settings['category_schema_type'] ?? '')),
            'category_schema_properties' => is_array($settings['category_schema_properties'] ?? null)
                ? $settings['category_schema_properties']
                : [],
        ];
    }

    private function isOwnCategory(object|array $data): bool
    {
        if (is_array($data)) {
            return ($data['extension'] ?? null) === self::CATEGORY_EXTENSION;
        }

        if (method_exists($data, 'get')) {
            return $data->get('extension') === self::CATEGORY_EXTENSION;
        }

        return ($data->extension ?? null) === self::CATEGORY_EXTENSION;
    }

    /** @return array{schema_type: string, schema_properties: array<string, mixed>} */
    private function getDefaultSchemaSettings(string $prefix): array
    {
        $properties = $this->params->get($prefix.'_schema_properties', []);

        if ($properties instanceof Registry) {
            $properties = $properties->toArray();
        } elseif (is_object($properties)) {
            $properties = (array) $properties;
        }

        return [
            'schema_type' => trim((string) $this->params->get($prefix.'_schema_type', '')),
            'schema_properties' => is_array($properties) ? $properties : [],
        ];
    }

    /** @return array{schema_type: string, schema_properties: mixed}|null */
    private function getInheritedCategorySchemaSettings(int $categoryId, string $scope): ?array
    {
        if ($categoryId < 1) {
            return null;
        }

        $categories = Categories::getInstance('contact', ['countItems' => false]);
        $category = is_object($categories) ? $categories->get($categoryId) : null;
        $visited = [];
        $typeKey = $scope === 'item' ? 'item_schema_type' : 'category_schema_type';
        $propertiesKey = $scope === 'item' ? 'item_schema_properties' : 'category_schema_properties';

        while (is_object($category)) {
            $currentId = (int) ($category->id ?? 0);

            if ($currentId < 2 || isset($visited[$currentId])) {
                break;
            }

            $visited[$currentId] = true;
            $storedSettings = $this->itemModel->getParamsByContext(self::CATEGORY_CONTEXT, $currentId);
            $schemaType = trim((string) ($storedSettings[$typeKey] ?? ''));

            if ($schemaType !== '') {
                return [
                    'schema_type' => $schemaType,
                    'schema_properties' => $storedSettings[$propertiesKey] ?? [],
                ];
            }

            $category = method_exists($category, 'getParent') ? $category->getParent() : null;
        }

        return null;
    }

    private function prepareMenuData(PrepareDataEvent $event, object|array $data): void
    {
        $target = $this->getMenuTarget($data);

        if ($target === null) {
            return;
        }

        $settings = $this->getMicroschemaData($data);

        $keys = $this->getMenuSettingKeys($target);

        if ($keys !== [] && array_diff($keys, array_keys($settings)) === []) {
            return;
        }

        $itemId = $this->getItemId($data);

        if ($itemId < 1) {
            return;
        }

        $storedSettings = $this->itemModel->getParamsByContext(self::MENU_STORAGE_CONTEXT, $itemId);

        if ($storedSettings === []) {
            return;
        }

        $this->setMicroschemaData($event, $data, array_replace($storedSettings, $settings));
    }

    private function saveMenuData(AfterSaveEvent $event): void
    {
        $itemId = (int) ($event->getItem()->id ?? 0);

        if ($itemId < 1) {
            return;
        }

        $data = $event->getData();

        if (!is_array($data) && !is_object($data)) {
            $application = $this->getApplication();
            $input = method_exists($application, 'getInput') ? $application->getInput() : null;
            $data = is_object($input) && method_exists($input, 'get')
                ? $input->get('jform', [], 'array')
                : [];
            $data = is_array($data) ? $data : [];
        }

        $target = $this->getMenuTarget($data)
            ?? $this->getMenuTarget($event->getItem());

        if ($target === null) {
            if (!$this->itemModel->deleteItemByContext(self::MENU_STORAGE_CONTEXT, $itemId)) {
                throw new \RuntimeException('Unable to delete the MicroSchema contact menu settings.');
            }

            return;
        }

        $submitted = $this->getMicroschemaData($data);
        $keys = $this->getMenuSettingKeys($target);

        if ($keys === [] || array_intersect($keys, array_keys($submitted)) === []) {
            return;
        }

        $settings = $this->normaliseMenuSettings($submitted, $keys);

        if (!$this->hasEnabledMenuSchema($settings)) {
            if (!$this->itemModel->deleteItemByContext(self::MENU_STORAGE_CONTEXT, $itemId)) {
                throw new \RuntimeException('Unable to delete the MicroSchema contact menu settings.');
            }

            return;
        }

        if (!$this->itemModel->saveParamsByContext(self::MENU_STORAGE_CONTEXT, $itemId, $settings)) {
            throw new \RuntimeException('Unable to save the MicroSchema contact menu settings.');
        }
    }

    /**
     * @return array{settings: array{schema_type: string, schema_properties: mixed}, menuItemId: int}|null
     */
    private function getActiveMenuSchemaSettings(string $scope): ?array
    {
        $application = $this->getApplication();

        if (!method_exists($application, 'getMenu')) {
            return null;
        }

        $menu = $application->getMenu();

        if (!is_object($menu) || !method_exists($menu, 'getActive')) {
            return null;
        }

        $active = $menu->getActive();

        if (!is_object($active)) {
            return null;
        }

        $target = $this->getMenuTarget($active);
        $menuItemId = $this->getItemId($active);

        $keys = match (true) {
            $scope === 'item' && $target === 'contact' => ['schema_type', 'schema_properties'],
            $scope === 'item' && in_array($target, ['category', 'featured'], true) => ['item_schema_type', 'item_schema_properties'],
            $scope === 'category' && in_array($target, ['category', 'categories'], true) => ['category_schema_type', 'category_schema_properties'],
            default => [],
        };

        if ($menuItemId < 1 || $keys === []) {
            return null;
        }

        $storedSettings = $this->itemModel->getParamsByContext(self::MENU_STORAGE_CONTEXT, $menuItemId);
        [$typeKey, $propertiesKey] = $keys;
        $schemaType = trim((string) ($storedSettings[$typeKey] ?? ''));

        if ($schemaType === '') {
            return null;
        }

        return [
            'settings' => [
                'schema_type' => $schemaType,
                'schema_properties' => $storedSettings[$propertiesKey] ?? [],
            ],
            'menuItemId' => $menuItemId,
        ];
    }

    private function getMenuTarget(object|array $data): ?string
    {
        $query = $this->getMenuQuery($data);

        if (($query['option'] ?? '') !== self::CATEGORY_EXTENSION) {
            return null;
        }

        return match ((string) ($query['view'] ?? '')) {
            'contact' => 'contact',
            'category' => 'category',
            'featured' => 'featured',
            'categories' => 'categories',
            default => null,
        };
    }

    /** @return list<string> */
    private function getMenuSettingKeys(string $target): array
    {
        return match ($target) {
            'contact' => ['schema_type', 'schema_properties'],
            'category' => [
                'item_schema_type',
                'item_schema_properties',
                'category_schema_type',
                'category_schema_properties',
            ],
            'featured' => [
                'page_schema_type',
                'page_schema_properties',
                'item_schema_type',
                'item_schema_properties',
            ],
            'categories' => [
                'page_schema_type',
                'page_schema_properties',
                'category_schema_type',
                'category_schema_properties',
            ],
            default => [],
        };
    }

    /**
     * @param array<string, mixed> $settings
     * @param list<string>         $keys
     *
     * @return array<string, mixed>
     */
    private function normaliseMenuSettings(array $settings, array $keys): array
    {
        $normalised = [];

        foreach ($keys as $key) {
            $normalised[$key] = str_ends_with($key, '_properties')
                ? (is_array($settings[$key] ?? null) ? $settings[$key] : [])
                : trim((string) ($settings[$key] ?? ''));
        }

        return $normalised;
    }

    /** @param array<string, mixed> $settings */
    private function hasEnabledMenuSchema(array $settings): bool
    {
        foreach ($settings as $key => $value) {
            if (str_ends_with($key, '_type') && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    private function getMenuQuery(object|array $data): array
    {
        foreach (['query', 'request'] as $key) {
            $query = $this->readData($data, $key, []);

            if ($query instanceof Registry) {
                $query = $query->toArray();
            } elseif (is_object($query)) {
                $query = (array) $query;
            }

            if (is_array($query) && isset($query['option'], $query['view'])) {
                return $query;
            }
        }

        $link = (string) $this->readData($data, 'link', '');

        if ($link === '') {
            return [];
        }

        $queryString = parse_url($link, PHP_URL_QUERY);

        if (!is_string($queryString)) {
            return [];
        }

        parse_str($queryString, $query);

        return is_array($query) ? $query : [];
    }

    private function readData(object|array $data, string $key, mixed $default = null): mixed
    {
        if (is_array($data)) {
            return $data[$key] ?? $default;
        }

        if (method_exists($data, 'get')) {
            return $data->get($key, $default);
        }

        return $data->{$key} ?? $default;
    }

    /** @param array<string, mixed> $settings */
    private function setMicroschemaData(PrepareDataEvent $event, object|array $data, array $settings): void
    {
        if (is_array($data)) {
            $data['microschema'] = $settings;
        } elseif (method_exists($data, 'set')) {
            $data->set('microschema', $settings);
        } else {
            $data->microschema = $settings;
        }

        $event->updateData($data);
    }

    /** @param array<string, mixed> $settings */
    private function isSchemaDisabled(array $settings): bool
    {
        return trim((string) ($settings['schema_type'] ?? '')) === self::SCHEMA_DISABLED;
    }

    private function getItemId(object|array $data): int
    {
        if (is_array($data)) {
            return (int) ($data['id'] ?? 0);
        }

        if (method_exists($data, 'get')) {
            return (int) $data->get('id', 0);
        }

        return (int) ($data->id ?? 0);
    }
}
