<?php

namespace Joomla\Plugin\Microschema\Contact\DataCollection;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Language\Text;
use Joomla\Component\Microschema\Administrator\DataCollection\DataCollectionInterface;
use Joomla\Component\Microschema\Administrator\DataCollection\DataCollectionResult;
use Joomla\Component\Microschema\Administrator\DataSource\DataContext;

final readonly class CategoryContactsCollection implements DataCollectionInterface
{
    private const CATEGORY_CONTEXT = 'com_contact.categories';

    private const CONTACT_CONTEXT = 'com_contact.contact';

    public function __construct(private CMSApplicationInterface $application)
    {
    }

    public function getName(): string
    {
        return 'contact.category.contacts';
    }

    public function getLabel(): string
    {
        return Text::_('PLG_MICROSCHEMA_CONTACT_DATA_COLLECTION_CATEGORY_CONTACTS');
    }

    public function supportsContext(string $context): bool
    {
        return $context === self::CATEGORY_CONTEXT;
    }

    public function getPreviewContext(DataContext $context): DataContext
    {
        return new DataContext(self::CONTACT_CONTEXT, 0, []);
    }

    public function getItems(DataContext $context): DataCollectionResult
    {
        if (!$this->supportsContext($context->context) || !$this->application->isClient('site')) {
            return new DataCollectionResult([]);
        }

        $model = $this->application->bootComponent('com_contact')->getMVCFactory()->createModel(
            'Category',
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

            $contexts[] = new DataContext(self::CONTACT_CONTEXT, (int) $item->id, $item);
        }

        $pagination = method_exists($model, 'getPagination') ? $model->getPagination() : null;
        $offset = is_object($pagination) ? max(0, (int) ($pagination->limitstart ?? 0)) : 0;
        $total = is_object($pagination) ? max(count($contexts), (int) ($pagination->total ?? 0)) : count($contexts);

        return new DataCollectionResult($contexts, $offset, $total);
    }
}
