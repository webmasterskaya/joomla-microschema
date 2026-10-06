<?php

/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\Microschema\Extension;

defined('_JEXEC') || exit;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Event\Application\AfterInitialiseEvent;
use Joomla\CMS\Event\Application\AfterRenderEvent;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Component\Microschema\Administrator\DataSource\SiteDataSource;
use Joomla\Component\Microschema\Administrator\Event\CollectSchemasEvent;
use Joomla\Component\Microschema\Administrator\Event\RegisterDataSourcesEvent;
use Joomla\Component\Microschema\Administrator\Event\RegisterDataTypesEvent;
use Joomla\Component\Microschema\Administrator\Event\RegisterMetadataEvent;
use Joomla\Component\Microschema\Administrator\Extension\MicroschemaComponent;
use Joomla\Component\Microschema\Administrator\Metadata;
use Joomla\Component\Microschema\Administrator\Metadata\ExistingSocialMetadataChecker;
use Joomla\Component\Microschema\Administrator\Metadata\SocialMetadataInjector;
use Joomla\Component\Microschema\Administrator\Metadata\SocialMetadataRenderer;
use Joomla\Component\Microschema\Administrator\Schema\BreadcrumbListBuilder;
use Joomla\Component\Microschema\Administrator\Schema\ExistingSchemaChecker;
use Joomla\Component\Microschema\Administrator\Schema\JsonLdRenderer;
use Joomla\Component\Microschema\Administrator\Schema\SchemaDataBuilder;
use Joomla\Component\Microschema\Administrator\Schema\SchemaDateEnricher;
use Joomla\Component\Microschema\Administrator\Schema\SchemaIdentityEnricher;
use Joomla\Component\Microschema\Administrator\Schema\SchemaLanguageEnricher;
use Joomla\Component\Microschema\Administrator\Schema\SchemaMarkupInjector;
use Joomla\Component\Microschema\Administrator\Schema\SchemaResolver;
use Joomla\Component\Microschema\Administrator\Schema\SchemaUrlEnricher;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\System\Microschema\DataSource\CategoryDataSource;
use Joomla\Plugin\System\Microschema\DataSource\MenuItemDataSource;
use Joomla\Plugin\System\Microschema\DataType\CustomFieldsDataType;
use Joomla\Plugin\System\Microschema\DataType\JoomlaCategoryDataType;
use Joomla\Plugin\System\Microschema\DataType\JoomlaMenuItemDataType;
use Joomla\Plugin\System\Microschema\DataType\JoomlaUserDataType;
use Joomla\Plugin\System\Microschema\DataType\SiteDataType;
use Joomla\Registry\Registry;

final class Microschema extends CMSPlugin implements SubscriberInterface, DispatcherAwareInterface
{
    use DispatcherAwareTrait;

    protected $autoloadLanguage = true;

    private bool $schemasRendered = false;

    public function __construct(
        array $config,
        private readonly Registry $configuration,
        private readonly SchemaResolver $schemaResolver,
        private readonly SchemaLanguageEnricher $schemaLanguageEnricher,
        private readonly SchemaUrlEnricher $schemaUrlEnricher,
        private readonly SchemaIdentityEnricher $schemaIdentityEnricher,
        private readonly SchemaDateEnricher $schemaDateEnricher,
        private readonly JsonLdRenderer $jsonLdRenderer,
        private readonly ExistingSchemaChecker $existingSchemaChecker,
        private readonly SchemaMarkupInjector $schemaMarkupInjector,
        private readonly SocialMetadataRenderer $socialMetadataRenderer,
        private readonly ExistingSocialMetadataChecker $existingSocialMetadataChecker,
        private readonly SocialMetadataInjector $socialMetadataInjector,
        private readonly UserFactoryInterface $userFactory,
    ) {
        parent::__construct($config);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterInitialise' => 'onAfterInitialise',
            'onAfterRender' => 'onAfterRender',
        ];
    }

    public function onAfterInitialise(AfterInitialiseEvent $event): void
    {
        PluginHelper::importPlugin('microschema', null, true, $this->getDispatcher());
    }

    public function onAfterRender(AfterRenderEvent $event): void
    {
        if ($this->schemasRendered) {
            return;
        }

        /** @var \Joomla\CMS\Application\CMSApplication $application */
        $application = $event->getApplication();
        $document = $application->getDocument();

        if (
            !$application->isClient('site')
            || !$document instanceof HtmlDocument
            || $application->getInput()->get('option', '', 'cmd') === 'com_ajax'
        ) {
            return;
        }

        $this->schemasRendered = true;

        $component = $application->bootComponent('com_microschema');

        if (!$component instanceof MicroschemaComponent) {
            return;
        }

        $collector = $component->getSchemaCollector();
        $componentParams = ComponentHelper::getParams('com_microschema');

        if ((bool) $componentParams->get('schemaorg_enabled', true)) {
            $this->addGlobalSchemas($component, $componentParams);
        }

        $this->getDispatcher()->dispatch(
            CollectSchemasEvent::NAME,
            new CollectSchemasEvent($collector),
        );

        $body = (string) $application->getBody();
        $schemaOrgEnabled = (bool) $componentParams->get('schemaorg_enabled', true);
        $schemas = $schemaOrgEnabled
            ? $this->schemaResolver->resolve($collector->getPath(), $collector->getRegistry())
            : [];

        if ($schemaOrgEnabled) {
            $descriptors = $component->getMetadataRegistry()->getSchemaOrg();
            $schemas = $this->schemaLanguageEnricher->enrich(
                $schemas,
                $descriptors,
                $application->getLanguage()->getTag(),
            );
            $schemas = $this->schemaUrlEnricher->enrich(
                $schemas,
                $descriptors,
                Uri::root(),
            );
            $schemas = $this->schemaIdentityEnricher->enrich(
                $schemas,
                Uri::root(),
                Uri::getInstance()->toString(['scheme', 'host', 'port', 'path', 'query']),
                (bool) $componentParams->get('breadcrumblist_enabled', true),
            );
            $schemas = $this->schemaDateEnricher->enrich(
                $schemas,
                $descriptors,
                (string) $this->configuration->get('offset', 'UTC'),
            );
        }

        if ($schemaOrgEnabled && (bool) $componentParams->get('schemaorg_check_existing_markup', false)) {
            $schemas = $this->existingSchemaChecker->filter($body, $schemas);
        }

        $tags = $this->jsonLdRenderer->render($schemas);
        $position = (string) $componentParams->get('output_position', SchemaMarkupInjector::POSITION_HEAD);
        $body = $this->schemaMarkupInjector->inject($body, $tags, $position);

        if ((bool) $componentParams->get('socials_enabled', true)) {
            $socialMetadata = $this->schemaResolver->resolve(
                $collector->getPath(),
                $collector->getSocialRegistry(),
            );
            $enabledSocials = (array) $componentParams->get('socials', []);
            $socialTags = $this->socialMetadataRenderer->render(
                $socialMetadata,
                $component->getMetadataRegistry()->getSocials(),
                $enabledSocials,
            );

            if ((bool) $componentParams->get('socials_check_existing_markup', false)) {
                $socialTags = $this->existingSocialMetadataChecker->filter($body, $socialTags);
            }

            $body = $this->socialMetadataInjector->inject($body, $socialTags);
        }

        $application->setBody($body);
    }

    private function addGlobalSchemas(MicroschemaComponent $component, Registry $params): void
    {
        $collector = $component->getSchemaCollector();
        $builder = new SchemaDataBuilder($component->getMetadataRegistry()->getSchemaOrg());

        foreach ([
            'Organization' => ['enabled' => 'organization_enabled', 'properties' => 'organization_properties'],
            'WebSite' => ['enabled' => 'website_enabled', 'properties' => 'website_properties'],
        ] as $type => $settings) {
            if (!(bool) $params->get($settings['enabled'], true)) {
                continue;
            }

            $schema = $builder->build($type, $params->get($settings['properties'], []));

            if (count($schema) > 1) {
                $collector->addSchema('global.'.strtolower($type), $schema);
            }
        }

        if (!(bool) $params->get('breadcrumblist_enabled', true)) {
            return;
        }

        $application = $this->getApplication();
        $entries = [];
        $menu = $application->getMenu();
        $language = $application->getLanguage();
        $home = Multilanguage::isEnabled()
            ? $menu->getDefault($language->getTag())
            : $menu->getDefault();

        if ($this->isHomePage($home, $menu->getActive())) {
            return;
        }

        if (is_object($home)) {
            $entries[] = [
                'name' => $this->normalizeBreadcrumbName((string) $home->title),
                'url' => Route::_('index.php?Itemid='.(int) $home->id, true, 0, true),
            ];
        }

        foreach ($application->getPathway()->getPathway() as $item) {
            $entries[] = [
                'name' => $this->normalizeBreadcrumbName((string) ($item->name ?? '')),
                'url' => !empty($item->link)
                    ? Route::_((string) $item->link, true, 0, true)
                    : Uri::current(),
            ];
        }

        $breadcrumb = (new BreadcrumbListBuilder())->build($entries);

        if ($breadcrumb !== []) {
            $collector->addSchema('global.breadcrumb', $breadcrumb);
        }
    }

    private function isHomePage(?object $home, ?object $active): bool
    {
        return is_object($home)
            && is_object($active)
            && (int) ($home->id ?? 0) > 0
            && (int) ($home->id ?? 0) === (int) ($active->id ?? 0);
    }

    private function normalizeBreadcrumbName(string $name): string
    {
        return trim(html_entity_decode(strip_tags($name), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public function registerMetadata(RegisterMetadataEvent $event): void
    {
        foreach ([
            Metadata\SchemaOrg\Article::class,
            Metadata\SchemaOrg\BlogPosting::class,
            Metadata\SchemaOrg\BreadcrumbList::class,
            Metadata\SchemaOrg\CollectionPage::class,
            Metadata\SchemaOrg\Course::class,
            Metadata\SchemaOrg\Event::class,
            Metadata\SchemaOrg\FAQPage::class,
            Metadata\SchemaOrg\HowTo::class,
            Metadata\SchemaOrg\JobPosting::class,
            Metadata\SchemaOrg\ItemList::class,
            Metadata\SchemaOrg\LocalBusiness::class,
            Metadata\SchemaOrg\NewsArticle::class,
            Metadata\SchemaOrg\Organization::class,
            Metadata\SchemaOrg\Person::class,
            Metadata\SchemaOrg\Product::class,
            Metadata\SchemaOrg\Recipe::class,
            Metadata\SchemaOrg\Review::class,
            Metadata\SchemaOrg\Service::class,
            Metadata\SchemaOrg\VideoObject::class,
            Metadata\SchemaOrg\WebPage::class,
            Metadata\SchemaOrg\WebSite::class,
        ] as $descriptorClass) {
            $event->registerSchemaOrg($descriptorClass);
        }

        foreach ([
            Metadata\SchemaOrg\AggregateOffer::class,
            Metadata\SchemaOrg\AggregateRating::class,
            Metadata\SchemaOrg\Brand::class,
            Metadata\SchemaOrg\ContactPoint::class,
            Metadata\SchemaOrg\Demand::class,
            Metadata\SchemaOrg\HowToStep::class,
            Metadata\SchemaOrg\ImageObject::class,
            Metadata\SchemaOrg\ListItem::class,
            Metadata\SchemaOrg\Offer::class,
            Metadata\SchemaOrg\PostalAddress::class,
        ] as $descriptorClass) {
            $event->registerSchemaOrg($descriptorClass, false);
        }

        foreach ([
            Metadata\Social\Facebook::class,
            Metadata\Social\OpenGraph::class,
            Metadata\Social\TwitterCard::class,
            Metadata\Social\Vk::class,
        ] as $descriptorClass) {
            $event->registerSocial($descriptorClass);
        }
    }

    public function registerDataSources(RegisterDataSourcesEvent $event): void
    {
        $event->register(new SiteDataSource(
            $this->configuration,
            Uri::getInstance()->toString(['scheme', 'host', 'port', 'path', 'query']),
            Uri::root(),
        ));
        $event->register(new CategoryDataSource());
        $event->register(new MenuItemDataSource($this->getApplication()));
    }

    public function registerDataTypes(RegisterDataTypesEvent $event): void
    {
        $event->register(new SiteDataType());
        $event->register(new CustomFieldsDataType());
        $event->register(new JoomlaCategoryDataType());
        $event->register(new JoomlaMenuItemDataType());
        $event->register(new JoomlaUserDataType($this->userFactory));
    }
}
