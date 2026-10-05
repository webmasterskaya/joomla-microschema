<?php

declare(strict_types=1);

namespace Joomla\CMS\Application {
    interface CMSApplicationInterface
    {
        public function isClient($identifier);
    }
}

namespace Joomla\CMS\Language {
    final class Text
    {
        public static function _(string $key): string
        {
            return $key;
        }
    }
}

namespace Joomla\CMS\Router {
    final class Route
    {
        public static function _(string $url, bool $xhtml = true, int $tls = 0, bool $absolute = false): string
        {
            return $absolute ? 'https://example.test/' . $url : $url;
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
    use Joomla\CMS\Application\CMSApplicationInterface;
    use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
    use Joomla\Plugin\System\Microschema\DataSource\MenuItemDataSource;
    use Joomla\Plugin\System\Microschema\DataType\JoomlaMenuItemDataType;
    use Joomla\Registry\Registry;

    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataContext.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataSourceInterface.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataSourceField.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataTypeInterface.php';
    require_once __DIR__ . '/../../../plugins/system/microschema/src/DataSource/MenuItemDataSource.php';
    require_once __DIR__ . '/../../../plugins/system/microschema/src/DataType/JoomlaMenuItemDataType.php';

    function assertMenuItemSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException(
                $message . PHP_EOL
                . 'Expected: ' . var_export($expected, true) . PHP_EOL
                . 'Actual: ' . var_export($actual, true),
            );
        }
    }

    $parent = new class () {
        public int $id = 12;
        public string $title = 'Parent item';
    };
    $active = new class ($parent) {
        public int $id = 34;
        public string $title = 'Current page';
        public string $alias = 'current-page';
        public string $menutype = 'mainmenu';
        public string $type = 'component';
        public string $language = 'en-GB';
        public int $level = 2;
        public int $home = 0;

        public function __construct(private readonly object $parent)
        {
        }

        public function getParams(): Registry
        {
            return new Registry([
                'page_heading'          => 'Page heading',
                'page_title'            => 'Browser title',
                'menu-meta_description' => 'Menu description',
                'menu_image'            => 'images/menu/current.svg',
            ]);
        }

        public function getParent(): object
        {
            return $this->parent;
        }
    };
    $fallback = (object) ['id' => 56, 'title' => 'Fallback page'];
    $menu = new class ($active, $fallback) {
        public ?object $active;

        public function __construct(object $active, private readonly object $fallback)
        {
            $this->active = $active;
        }

        public function getActive(): ?object
        {
            return $this->active;
        }

        public function getItem(int $id): ?object
        {
            return $id === 56 ? $this->fallback : null;
        }
    };
    $application = new class ($menu) implements CMSApplicationInterface {
        public function __construct(private readonly object $menu)
        {
        }

        public function isClient($identifier): bool
        {
            return $identifier === 'site';
        }

        public function getMenu(): object
        {
            return $this->menu;
        }

        public function getInput(): object
        {
            return new class () {
                public function getInt(string $name): int
                {
                    return $name === 'Itemid' ? 56 : 0;
                }
            };
        }
    };
    $context = new DataContext('com_content.article', 7, (object) ['id' => 7]);
    $source  = new MenuItemDataSource($application);
    $type    = new JoomlaMenuItemDataType();

    assertMenuItemSame('menuItem', $source->getName(), 'The menu item source name must be stable.');
    assertMenuItemSame('PLG_SYSTEM_MICROSCHEMA_DATA_SOURCE_MENU_ITEM', $source->getLabel(), 'The source label must be translatable.');
    assertMenuItemSame(true, $source->supportsContext('com_contact.contact'), 'The source must support every entity context.');
    assertMenuItemSame('JoomlaMenuItem', $source->getType(), 'The source must reference its reusable object type.');
    assertMenuItemSame($active, $source->getValue($context), 'The source must return the active frontend menu item.');

    $fieldsByName = [];

    foreach ($type->getFields($active, $context) as $field) {
        $fieldsByName[$field->name] = $field;
    }

    assertMenuItemSame(14, count($fieldsByName), 'Only the curated menu item fields must be exposed.');
    assertMenuItemSame('URL', $fieldsByName['link']->type ?? null, 'The menu item link must be declared as a URL.');
    assertMenuItemSame('JoomlaMenuItem', $fieldsByName['parent']->type ?? null, 'The parent must reuse the menu item type.');
    assertMenuItemSame('Current page', $type->resolve($active, 'title', $context), 'Direct menu item fields must resolve.');
    assertMenuItemSame('Page heading', $type->resolve($active, 'page_heading', $context), 'Curated menu parameters must resolve.');
    assertMenuItemSame('Browser title', $type->resolve($active, 'page_title', $context), 'The browser title must resolve.');
    assertMenuItemSame('Menu description', $type->resolve($active, 'menu-meta_description', $context), 'The menu meta description must resolve.');
    assertMenuItemSame('images/menu/current.svg', $type->resolve($active, 'menu_image', $context), 'The menu image must resolve.');
    assertMenuItemSame('https://example.test/index.php?Itemid=34', $type->resolve($active, 'link', $context), 'The source must create an absolute Itemid-aware link.');
    assertMenuItemSame(false, $type->resolve($active, 'home', $context), 'The home flag must resolve as a boolean.');
    assertMenuItemSame($parent, $type->resolve($active, 'parent', $context), 'The parent menu item must resolve as its own object.');

    $emptyImageItem = ['id' => 1, 'params' => ['menu_image' => '-1']];
    assertMenuItemSame(null, $type->resolve($emptyImageItem, 'menu_image', $context), 'The Joomla no-image sentinel must resolve as empty.');

    $menu->active = null;
    assertMenuItemSame($fallback, $source->getValue($context), 'The source must fall back to the request Itemid.');

    $administrator = new class () implements CMSApplicationInterface {
        public function isClient($identifier): bool
        {
            return $identifier === 'administrator';
        }
    };
    assertMenuItemSame(
        null,
        (new MenuItemDataSource($administrator))->getValue($context),
        'Administrator menu state must never leak into data values.',
    );

    echo "Joomla menu item data type tests passed.\n";
}
