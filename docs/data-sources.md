# Пользовательские источники и типы данных

Динамические значения сохраняются в свойствах Schema.org как плейсхолдеры:

```text
{article.title}
{article.author.name}
{article.category.link}
{article.fields.event_location}
{article.category.fields.subtitle}
{contact.name}
{contact.user.name}
{contact.category.fields.department}
{contact.fields.office_hours}
{menuItem.title}
{menuItem.page_heading}
{menuItem.parent.title}
{site.currentUrl}
{site.baseUrl}
```

Первая часть — имя корневого источника. Остальные сегменты последовательно
разрешаются зарегистрированными типами данных.

Если строка состоит из одного плейсхолдера, исходный тип значения сохраняется.
В смешанном шаблоне допустимы только скалярные результаты. Корректный плейсхолдер
с пустым значением заменяется пустой строкой. Неизвестный источник, тип или поле
считается ошибкой и превращает весь шаблон в `null`.

Например, при пустом `MetaDesc` шаблон
`Тестовая статья - {site.MetaDesc}` возвращает `Тестовая статья - `, а шаблон с
ошибкой `Тестовая статья - {site.unknown}` возвращает `null`.

При включённом режиме отладки Joomla система разрешения записывает причины пустых и
отклонённых значений в журнал отладки с категорией `com_microschema`. Сообщения содержат
контекст, плейсхолдер, источник, тип или поле, но не содержат полученные данные.

Источник `site` также предоставляет два URL-поля. `{site.currentUrl}` содержит
абсолютный URL текущего запроса, включая строку запроса, а `{site.baseUrl}` —
абсолютный корневой URL установки Joomla. Оба значения доступны как тип `URL`.

## Разметка категорий

В формах категорий материалов и контактов доступны два независимых блока:

- разметка элементов категории — резервный шаблон для материалов или контактов,
  назначенных непосредственно этой категории;
- разметка категории — Schema.org для самой страницы категории.

Каждый тип разметки материала, контакта или категории поддерживает три состояния:

- конкретный Schema.org-тип формирует собственную разметку;
- «По умолчанию» продолжает поиск настройки у родителя;
- «Отключить» останавливает поиск и запрещает разметку на этом уровне.

Пустые значения, сохранённые до появления этих состояний, считаются значением
«По умолчанию». Поэтому отдельная миграция сохранённых настроек не требуется.

Индивидуальная разметка материала или контакта проверяется первой и полностью
заменяет унаследованные настройки. Поэтому она выводится даже тогда, когда у
категории выбрано «Отключить». Если материал или контакт оставлен «По умолчанию»,
поиск идёт от непосредственной категории через все родительские категории к
глобальному шаблону соответствующего интеграционного плагина.

Для страницы категории поиск аналогично идёт от самой категории через её
родителей к глобальному шаблону категорий. «Отключить» на любом найденном уровне
останавливает дальнейшее наследование. Очистка одного блока конкретной категории
не удаляет заполненный второй блок.

Итоговый порядок разрешения:

```text
Материал или контакт:
индивидуальная разметка → непосредственная категория → родительские категории
→ глобальный шаблон

Страница категории:
собственная разметка → родительские категории → глобальный шаблон категорий
```

У глобального шаблона нет родителя, поэтому его пустой вариант называется
«Отключить», а не «По умолчанию».

На странице категории доступен универсальный корневой источник `category`:

```text
{category.title}
{category.description}
{category.link}
{category.parent.title}
{category.fields.subtitle}
```

В блоке шаблона элементов источник `article` или `contact` показывается в
редакторе как объект соответствующего типа. Во время вывода он разрешается уже
на данных конкретного материала или контакта.

## Коллекции

Повторяемое свойство Schema.org можно заполнить статическими элементами либо
шаблоном коллекции. Шаблон хранится как обычное типизированное значение с
дополнительным стабильным именем коллекции:

```php
[
    'collection' => 'content.category.articles',
    'type'       => 'ListItem',
    'data'       => [
        'position' => '{iteration.position}',
        'name'     => '{article.title}',
        'url'      => '{article.link}',
    ],
]
```

Система разрешения получает от коллекции дочерние `DataContext` и применяет шаблон к
каждому из них. Поэтому поля элемента разрешают обычные источники и типы:
коллекция материалов не дублирует логику `ArticleDataSource` или
`ArticleDataType`.

Доступные данные итерации:

```text
{iteration.index}     — индекс от нуля на текущей странице
{iteration.position}  — позиция от единицы с учётом пагинации
{iteration.count}     — количество элементов на текущей странице
{iteration.total}     — общее количество элементов
```

На родительском уровне редактор также показывает каждую доступную коллекцию как
источник числовых значений. Поле `count` содержит количество элементов на текущей
странице, а `total` — полное количество с учётом фильтров модели Joomla. Например,
для `ItemList.numberOfItems` следует выбирать `total` той же коллекции, которая
используется в `itemListElement`. Результат коллекции кешируется на время разрешения
одной схемы, поэтому выбор статистики не вызывает повторную загрузку элементов.

Плагин материалов регистрирует коллекцию `content.category.articles`, а плагин
контактов — `contact.category.contacts`. Обе используют модель категории клиентской части
Joomla, поэтому учитывают текущую публикацию, доступ, язык, порядок и пагинацию.

Для страниц пунктов меню дополнительно доступны коллекции:

- `content.menu.articles` в контекстах `com_content.featured` и
  `com_content.archive`;
- `content.menu.categories` в контексте `com_content.menu.categories`;
- `contact.menu.contacts` в контексте `com_contact.featured`;
- `contact.menu.categories` в контексте `com_contact.menu.categories`.

Коллекции используют соответствующие модели клиентской части Joomla (`Featured`, `Archive`
и `Categories`). Поэтому шаблон страницы работает с тем же набором элементов,
который Joomla подготовила для активного пункта меню, включая ограничения
публикации, доступа, языка и пагинацию там, где её предоставляет модель.

Сторонняя интеграция может реализовать `DataCollectionInterface`, вернуть
предпросмотровый контекст для каталога редактора и `DataCollectionResult` со
списком контекстов элементов. Коллекция регистрируется через событие
`onMicroschemaRegisterDataCollections`.

## Источник и тип — разные роли

Источник предоставляет корневой объект, но не описывает его внутреннее устройство:

```php
interface DataSourceInterface
{
    public function getName(): string;
    public function getLabel(): string;
    public function supportsContext(string $context): bool;
    public function getType(): string;
    public function getValue(DataContext $context): mixed;
}
```

Тип объявляет доступные поля объекта и самостоятельно разрешает только один
непосредственный сегмент пути:

```php
interface DataTypeInterface
{
    public function getName(): string;

    /** @return list<DataSourceField> */
    public function getFields(mixed $value, DataContext $context): array;

    public function resolve(
        mixed $value,
        string $field,
        DataContext $context,
    ): mixed;
}
```

Например, `{article.author.name}` обрабатывается так:

1. Источник `article` возвращает текущий материал и тип `JoomlaArticle`.
2. `JoomlaArticle` разрешает поле `author` в контекстное значение пользователя.
3. Поле `author` ссылается на тип `JoomlaUser`.
4. `JoomlaUser` самостоятельно разрешает поле `name`.

`JoomlaArticle` не знает о полях пользователя. Поэтому другой тип может сослаться
на того же пользователя без дублирования:

```php
new DataSourceField('seller', 'Продавец', 'JoomlaUser');
```

После этого `{virtuemartProduct.seller.name}` проходит через тот же
`JoomlaUserDataType`.

Интеграция контактов устроена таким же образом. Источник `contact` доступен в
контексте `com_contact.contact` и возвращает тип `JoomlaContact`. Его поля
`user`, `category` и `fields` ссылаются на общие типы `JoomlaUser`,
`JoomlaCategory` и `JoomlaCustomFields`. Поэтому контакт отвечает только за
свои непосредственные данные, а вложенные сущности разрешают себя сами:

```text
{contact.email_to}
{contact.telephone}
{contact.user.name}
{contact.user.fields.biography}
{contact.category.link}
{contact.category.fields.department}
{contact.fields.office_hours}
```

## Описание поля

```php
new DataSourceField(
    name: 'author',
    label: 'Автор',
    type: 'JoomlaUser',
);
```

- `name` — стабильный сегмент пути;
- `label` — человекочитаемое название;
- `type` — скалярный тип либо имя зарегистрированного объектного типа.

Объектный тип регистрируется один раз, но каталог разворачивает его поля отдельно
для каждого пути. Благодаря этому один `JoomlaCustomFields` может показать поля
материала в `article.fields` и другой набор в `article.category.fields`.
Глубина каталога ограничена четырьмя объектными сегментами, поэтому циклические
связи вроде `category.parent` не создают бесконечную рекурсию.

Доступны все поля, явно объявленные методом `getFields()` соответствующего типа,
но не произвольные свойства PHP-объекта. Это исключает случайный доступ к
настройкам, служебным данным, секретам и самой Schema.org-разметке.

## Контекст

```php
final readonly class DataContext
{
    public function __construct(
        public string $context,
        public int $itemId,
        public object|array $item,
        public array $fieldValues = [],
    ) {
    }
}
```

Источник обязан проверять Joomla-контекст в `supportsContext()`. Для
неподдерживаемого контекста `getValue()` должен вернуть `null`.

Вложенная Joomla-сущность передаётся как контекстное значение:

```php
new ContextualDataValue(
    context: 'com_content.categories',
    itemId: $category->id,
    value: $category,
    overrides: ['link' => $categoryUrl],
);
```

Оно сохраняет контекст владельца независимо от корневой страницы. Тип
`JoomlaCustomFields` использует этот контекст через API полей Joomla. Поэтому
поля материала, категории, пользователя или сторонней сущности не смешиваются.

## Текущий пункт меню

Универсальный источник `menuItem` возвращает активный пункт меню текущего
запроса клиентской части. Это именно тот `Itemid`, через который посетитель открыл
материал, контакт, категорию или страницу стороннего компонента:

```text
{menuItem.title}
{menuItem.link}
{menuItem.page_heading}
{menuItem.page_title}
{menuItem.menu-meta_description}
{menuItem.menu_image}
{menuItem.parent.title}
```

Одна сущность Joomla может быть доступна через несколько пунктов меню, поэтому
источник не пытается найти один статически «привязанный» пункт. Он использует
активный пункт текущего запроса и при необходимости использует входной
`Itemid`. Если страница открыта без пункта меню, корректные плейсхолдеры
`menuItem` разрешаются в пустое значение.

Параметры пункта меню не публикуются целиком. В каталог включены только явно
перечисленные безопасные поля: заголовки страницы, метаописание, изображение,
ссылка и основные свойства самого пункта.

## Пример интеграции VirtueMart

Корневой источник:

```php
final class VirtuemartProductDataSource implements DataSourceInterface
{
    public function getName(): string
    {
        return 'virtuemartProduct';
    }

    public function getLabel(): string
    {
        return 'Товар VirtueMart';
    }

    public function supportsContext(string $context): bool
    {
        return $context === 'com_virtuemart.product';
    }

    public function getType(): string
    {
        return 'VirtuemartProduct';
    }

    public function getValue(DataContext $context): mixed
    {
        return $this->supportsContext($context->context)
            ? new ContextualDataValue(
                $context->context,
                $context->itemId,
                $context->item,
                $context->fieldValues,
            )
            : null;
    }
}
```

Тип товара:

```php
final class VirtuemartProductDataType implements DataTypeInterface
{
    public function getName(): string
    {
        return 'VirtuemartProduct';
    }

    public function getFields(mixed $value, DataContext $context): array
    {
        return [
            new DataSourceField('name', 'Название'),
            new DataSourceField('sku', 'Артикул'),
            new DataSourceField('price', 'Цена', 'Number'),
            new DataSourceField('seller', 'Продавец', 'JoomlaUser'),
            new DataSourceField('fields', 'Пользовательские поля', 'JoomlaCustomFields'),
        ];
    }

    public function resolve(mixed $product, string $field, DataContext $context): mixed
    {
        if (!$product instanceof ContextualDataValue) {
            return null;
        }

        if ($field === 'fields') {
            return $product;
        }

        $value = $product->value;

        return match ($field) {
            'name'   => $value->product_name ?? null,
            'sku'    => $value->product_sku ?? null,
            'price'  => $value->product_price ?? null,
            'seller' => new ContextualDataValue('com_users.user', (int) $value->created_by, $value->created_by),
            default  => null,
        };
    }
}
```

Тип товара возвращает только ссылку продавца. Имя, имя пользователя, адрес электронной почты и другие
поля разрешает зарегистрированный `JoomlaUserDataType`.

## Регистрация

Источники и типы регистрируются отдельными событиями:

```php
public static function getSubscribedEvents(): array
{
    return [
        RegisterDataSourcesEvent::NAME => 'registerDataSources',
        RegisterDataTypesEvent::NAME   => 'registerDataTypes',
    ];
}

public function registerDataSources(RegisterDataSourcesEvent $event): void
{
    $event->register(new VirtuemartProductDataSource());
}

public function registerDataTypes(RegisterDataTypesEvent $event): void
{
    $event->register(new VirtuemartProductDataType());
}
```

Если типу требуется репозиторий, база данных или Joomla-сервис, передавайте его
через конструктор из поставщика служб плагина. Например, `JoomlaUserDataType`
получает `UserFactoryInterface`, а не обращается к `Factory`.

## Рекомендации

- Тип разрешает только собственные непосредственные поля.
- Joomla-связь возвращает `ContextualDataValue`, чтобы дочерний тип получил контекст владельца.
- Один предметный тип регистрируется один раз и переиспользуется интеграциями.
- Пользовательские поля доступны через вложенное поле `fields`, а не отдельный корневой источник.
- Не объявляйте секретные и служебные свойства.
- Возвращайте `null` для неизвестных полей и отсутствующих значений.
- Сохраняйте имена источников, типов и полей стабильными между версиями.
