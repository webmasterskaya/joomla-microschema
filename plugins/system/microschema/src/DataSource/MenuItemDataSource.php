<?php

namespace Joomla\Plugin\System\Microschema\DataSource;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Language\Text;
use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
use Joomla\Component\Microschema\Administrator\DataSource\DataSourceInterface;

final readonly class MenuItemDataSource implements DataSourceInterface
{
    public function __construct(private CMSApplicationInterface $application)
    {
    }

    public function getName(): string
    {
        return 'menuItem';
    }

    public function getLabel(): string
    {
        return Text::_('PLG_SYSTEM_MICROSCHEMA_DATA_SOURCE_MENU_ITEM');
    }

    public function supportsContext(string $context): bool
    {
        return true;
    }

    public function getType(): string
    {
        return 'JoomlaMenuItem';
    }

    public function getValue(DataContext $context): mixed
    {
        if (!$this->application->isClient('site') || !method_exists($this->application, 'getMenu')) {
            return null;
        }

        $menu = $this->application->getMenu();

        if (!is_object($menu) || !method_exists($menu, 'getActive')) {
            return null;
        }

        $item = $menu->getActive();

        if (is_object($item)) {
            return $item;
        }

        if (!method_exists($menu, 'getItem') || !method_exists($this->application, 'getInput')) {
            return null;
        }

        $input = $this->application->getInput();
        $itemId = is_object($input) && method_exists($input, 'getInt')
            ? $input->getInt('Itemid')
            : 0;

        return $itemId > 0 ? $menu->getItem($itemId) : null;
    }
}
