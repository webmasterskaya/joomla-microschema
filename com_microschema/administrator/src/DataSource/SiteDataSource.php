<?php

namespace Joomla\Component\Microschema\Administrator\DataSource;

use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

final readonly class SiteDataSource implements DataSourceInterface
{
    public function __construct(
        private Registry $configuration,
        private string $currentUrl = '',
        private string $baseUrl = '',
    ) {
    }

    public function getName(): string
    {
        return 'site';
    }

    public function getLabel(): string
    {
        return Text::_('COM_MICROSCHEMA_DATA_SOURCE_SITE');
    }

    public function supportsContext(string $context): bool
    {
        return true;
    }

    public function getType(): string
    {
        return 'JoomlaSite';
    }

    public function getValue(DataContext $context): mixed
    {
        return [
            'sitename' => $this->configuration->get('sitename'),
            'MetaDesc' => $this->configuration->get('MetaDesc'),
            'currentUrl' => trim($this->currentUrl),
            'baseUrl' => trim($this->baseUrl),
        ];
    }
}
