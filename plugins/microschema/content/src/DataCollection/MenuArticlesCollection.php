<?php

namespace Joomla\Plugin\Microschema\Content\DataCollection;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Language\Text;
use Joomla\Component\Microschema\Administrator\DataCollection\DataCollectionInterface;
use Joomla\Component\Microschema\Administrator\DataCollection\DataCollectionResult;
use Joomla\Component\Microschema\Administrator\DataSource\DataContext;

final readonly class MenuArticlesCollection implements DataCollectionInterface
{
    private const ARTICLE_CONTEXT = 'com_content.article';

    public function __construct(private CMSApplicationInterface $application)
    {
    }

    public function getName(): string
    {
        return 'content.menu.articles';
    }

    public function getLabel(): string
    {
        return Text::_('PLG_MICROSCHEMA_CONTENT_DATA_COLLECTION_MENU_ARTICLES');
    }

    public function supportsContext(string $context): bool
    {
        return in_array($context, ['com_content.featured', 'com_content.archive'], true);
    }

    public function getPreviewContext(DataContext $context): DataContext
    {
        return new DataContext(self::ARTICLE_CONTEXT, 0, []);
    }

    public function getItems(DataContext $context): DataCollectionResult
    {
        if (!$this->supportsContext($context->context) || !$this->application->isClient('site')) {
            return new DataCollectionResult([]);
        }

        $modelName = $context->context === 'com_content.archive' ? 'Archive' : 'Featured';
        $model = $this->application->bootComponent('com_content')->getMVCFactory()->createModel(
            $modelName,
            'Site',
            [],
        );

        return $this->collectItems($model);
    }

    private function collectItems(mixed $model): DataCollectionResult
    {
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

            $contexts[] = new DataContext(self::ARTICLE_CONTEXT, (int) $item->id, $item);
        }

        $pagination = method_exists($model, 'getPagination') ? $model->getPagination() : null;
        $offset = is_object($pagination) ? max(0, (int) ($pagination->limitstart ?? 0)) : 0;
        $total = is_object($pagination) ? max(count($contexts), (int) ($pagination->total ?? 0)) : count($contexts);

        return new DataCollectionResult($contexts, $offset, $total);
    }
}
