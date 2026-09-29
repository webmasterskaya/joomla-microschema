<?php

namespace Joomla\Component\Microschema\Administrator\View\Microschema;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public function display($tpl = null): void
    {
        ToolbarHelper::title(Text::_('COM_MICROSCHEMA_DASHBOARD_TITLE'));

        parent::display($tpl);
    }
}
