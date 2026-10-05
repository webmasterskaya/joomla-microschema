<?php

declare(strict_types=1);

namespace Joomla\CMS\Language {
    class Text
    {
        public static function _(string $key): string
        {
            return $key;
        }
    }
}

namespace Joomla\Registry {
    final class Registry
    {
        /** @param array<string, mixed> $values */
        public function __construct(private readonly array $values = [])
        {
        }

        public function get(string $key, mixed $default = null): mixed
        {
            return $this->values[$key] ?? $default;
        }
    }
}

namespace {
    use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
    use Joomla\Component\Microschema\Administrator\DataSource\SiteDataSource;
    use Joomla\Registry\Registry;
    use Joomla\Plugin\System\Microschema\DataType\SiteDataType;

    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataContext.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataSourceInterface.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataSourceField.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataTypeInterface.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/SiteDataSource.php';
    require_once __DIR__ . '/../../../plugins/system/microschema/src/DataType/SiteDataType.php';

    function assertSiteDataSourceSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException(
                $message . PHP_EOL
                . 'Expected: ' . var_export($expected, true) . PHP_EOL
                . 'Actual: ' . var_export($actual, true),
            );
        }
    }

    $source  = new SiteDataSource(
        new Registry([
            'sitename' => 'Example site',
            'MetaDesc' => 'Example description',
        ]),
        'https://example.test/blog/?start=20',
        'https://example.test/',
    );
    $context = new DataContext('com_content.article', 42, new stdClass());
    $type   = new SiteDataType();
    $fields = $type->getFields($source->getValue($context), $context);

    assertSiteDataSourceSame('site', $source->getName(), 'The source must have a stable machine name.');
    assertSiteDataSourceSame(
        'COM_MICROSCHEMA_DATA_SOURCE_SITE',
        $source->getLabel(),
        'The source must have a translatable administrator label.',
    );
    assertSiteDataSourceSame(true, $source->supportsContext(''), 'The source must support every context.');
    assertSiteDataSourceSame('JoomlaSite', $source->getType(), 'The source must reference the site type.');
    assertSiteDataSourceSame(true, is_array($source->getValue($context)), 'The source must expose only its declared public values.');
    assertSiteDataSourceSame(4, count($fields), 'The source must expose site configuration and URL values.');
    assertSiteDataSourceSame('sitename', $fields[0]->name, 'The first field must select the site name.');
    assertSiteDataSourceSame(
        'COM_MICROSCHEMA_DATA_SOURCE_SITE_NAME',
        $fields[0]->label,
        'The site name option must have a translatable label.',
    );
    assertSiteDataSourceSame('String', $fields[0]->type, 'The site name field must be a string.');
    assertSiteDataSourceSame('MetaDesc', $fields[1]->name, 'The second field must select the site description.');
    assertSiteDataSourceSame(
        'COM_MICROSCHEMA_DATA_SOURCE_SITE_DESCRIPTION',
        $fields[1]->label,
        'The site description option must have a translatable label.',
    );
    assertSiteDataSourceSame('String', $fields[1]->type, 'The site description field must be a string.');
    assertSiteDataSourceSame('currentUrl', $fields[2]->name, 'The third field must select the current URL.');
    assertSiteDataSourceSame(
        'COM_MICROSCHEMA_DATA_SOURCE_SITE_CURRENT_URL',
        $fields[2]->label,
        'The current URL option must have a translatable label.',
    );
    assertSiteDataSourceSame('URL', $fields[2]->type, 'The current URL field must be typed as URL.');
    assertSiteDataSourceSame('baseUrl', $fields[3]->name, 'The fourth field must select the base URL.');
    assertSiteDataSourceSame(
        'COM_MICROSCHEMA_DATA_SOURCE_SITE_BASE_URL',
        $fields[3]->label,
        'The base URL option must have a translatable label.',
    );
    assertSiteDataSourceSame('URL', $fields[3]->type, 'The base URL field must be typed as URL.');
    assertSiteDataSourceSame(
        'Example site',
        $type->resolve($source->getValue($context), 'sitename', $context),
        'The site type must resolve the configured site name.',
    );
    assertSiteDataSourceSame(
        'Example description',
        $type->resolve($source->getValue($context), 'MetaDesc', $context),
        'The site type must resolve the configured site description.',
    );
    assertSiteDataSourceSame(
        'https://example.test/blog/?start=20',
        $type->resolve($source->getValue($context), 'currentUrl', $context),
        'The site type must resolve the absolute current request URL.',
    );
    assertSiteDataSourceSame(
        'https://example.test/',
        $type->resolve($source->getValue($context), 'baseUrl', $context),
        'The site type must resolve the absolute base URL.',
    );
    assertSiteDataSourceSame(null, $type->resolve($source->getValue($context), 'secret', $context), 'Unknown configuration keys must be rejected.');

    echo "Site data source tests passed.\n";
}
