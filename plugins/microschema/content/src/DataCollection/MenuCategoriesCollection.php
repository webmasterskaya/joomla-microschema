<?php

namespace Joomla\Plugin\Microschema\Content\DataCollection;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Language\Text;
use Joomla\Component\Microschema\Administrator\DataCollection\DataCollectionInterface;
use Joomla\Component\Microschema\Administrator\DataCollection\DataCollectionResult;
use Joomla\Component\Microschema\Administrator\DataSource\DataContext;

final readonly class MenuCategoriesCollection implements DataCollectionInterface
{
    private const MENU_CONTEXT = 'com_content.menu.categories';

    private const CATEGORY_CONTEXT = 'com_content.categories';

    public function __construct(private CMSApplicationInterface $application)
    {
    }

    public function getName(): string
    {
        return 'content.menu.categories';
    }

    public function getLabel(): string
    {
        return Text::_('PLG_MICROSCHEMA_CONTENT_DATA_COLLECTION_MENU_CATEGORIES');
    }

    public function supportsContext(string $context): bool
    {
        return $context === self::MENU_CONTEXT;
    }

    public function getPreviewContext(DataContext $context): DataContext
    {
        return new DataContext(self::CATEGORY_CONTEXT, 0, []);
    }

    public function getItems(DataContext $context): DataCollectionResult
    {
        if (!$this->supportsContext($context->context) || !$this->application->isClient('site')) {
            return new DataCollectionResult([]);
        }

        $model = $this->application->bootComponent('com_content')->getMVCFactory()->createModel(
            'Categories',
            'Site',
            [],
        );

        if (!is_object($model) || !method_exists($model, 'getItems')) {
            return new DataCollectionResult([]);
        }

        $items = $model->getItems();

        if (!is_array($items)) {
            return new DataCollectionResult([]);
        }

        $contexts = [];

        foreach ($items as $item) {
            if (!is_object($item) || (int) ($item->id ?? 0) < 1) {
                continue;
            }

            $contexts[] = new DataContext(self::CATEGORY_CONTEXT, (int) $item->id, $item);
        }

        return new DataCollectionResult($contexts, 0, count($contexts));
    }
}
