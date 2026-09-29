# Пользовательские типы метаданных

Дескрипторы метаданных описывают доступные свойства Schema.org и социальной мета-разметки. Компонент
`com_microschema` использует их при построении формы настройки и последующем формировании JSON-LD или HTML
метатегов.

Дескриптор не получает данные материала и не формирует готовую разметку. Он определяет имя типа, формат вывода и
набор поддерживаемых свойств. Источник фактического значения свойства выбирается отдельно.

Новые дескрипторы добавляются Joomla-плагином через событие `onMicroschemaRegisterMetadata`. Изменять код компонента
для этого не требуется.

## Встроенные типы

Компонент поставляется со следующими типами Schema.org:

| Имя типа | Назначение |
| --- | --- |
| `Article` | Статья |
| `BlogPosting` | Публикация блога |
| `BreadcrumbList` | Навигационная цепочка |
| `Course` | Учебный курс |
| `Event` | Событие |
| `FAQPage` | Страница вопросов и ответов |
| `HowTo` | Пошаговая инструкция |
| `JobPosting` | Вакансия |
| `LocalBusiness` | Локальная организация |
| `NewsArticle` | Новостная статья |
| `Organization` | Организация |
| `Person` | Человек |
| `Product` | Товар |
| `Recipe` | Рецепт |
| `Review` | Отзыв |
| `Service` | Услуга |
| `VideoObject` | Видео |

Встроенные форматы социальной мета-разметки:

| Имя типа | Формат |
| --- | --- |
| `Facebook` | Метаданные Facebook |
| `OpenGraph` | Open Graph |
| `TwitterCard` | Twitter Card |
| `Vk` | Метаданные VK |

Имена из таблиц являются машинными идентификаторами. Они используются в конфигурации и должны оставаться
стабильными между версиями расширения.

## Контракт дескриптора

Каждый тип должен реализовывать интерфейс
`Joomla\Component\Microschema\Administrator\Metadata\DescriptorInterface`:

```php
interface DescriptorInterface
{
    public function getName(): string;

    public function getFormat(): Format;

    /** @return list<PropertyDefinition> */
    public function getProperties(): array;
}
```

Методы интерфейса:

- `getName()` возвращает уникальное машинное имя типа. Оно сохраняется в конфигурации и используется как ключ
  реестра.
- `getFormat()` возвращает формат результата: `Format::JSON_LD` или `Format::META`.
- `getProperties()` возвращает список поддерживаемых свойств разметки.

Вместо прямой реализации интерфейса рекомендуется наследовать `AbstractDescriptor`. Базовый класс возвращает
`Format::JSON_LD` и предоставляет защищённый метод `property()` для создания свойств.

Реестр создаёт дескриптор выражением `new $descriptorClass()`. Поэтому класс должен иметь публичный конструктор без
обязательных аргументов.

## Свойства разметки

Метод `getProperties()` возвращает список объектов `PropertyDefinition`:

```php
new PropertyDefinition(
    name: 'image',
    types: ['URL', 'ImageObject'],
    required: false,
    multiple: true,
    tag: null,
);
```

Параметры свойства:

| Параметр | Назначение |
| --- | --- |
| `name` | Машинное имя свойства |
| `types` | Список допустимых типов значения; по умолчанию `string` |
| `required` | Признак обязательного свойства |
| `multiple` | Разрешено ли несколько значений |
| `tag` | Имя HTML-метатега для формата `META` |

При наследовании `AbstractDescriptor` ту же структуру можно создать сокращённой записью:

```php
$this->property(
    name: 'image',
    types: ['URL', 'ImageObject'],
    multiple: true,
);
```

Для Schema.org параметр `tag` обычно не задаётся. Для социальной мета-разметки он содержит фактическое имя
метатега, например `og:title`.

## Пример типа Schema.org

Ниже приведён дескриптор типа `Book`:

```php
<?php

namespace Vendor\Plugin\Microschema\Library\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class Book extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'Book';
    }

    public function getProperties(): array
    {
        return [
            $this->property('name', required: true),
            $this->property('description'),
            $this->property('author', ['Person', 'Organization'], multiple: true),
            $this->property('isbn'),
            $this->property('image', ['URL', 'ImageObject'], multiple: true),
            $this->property('datePublished', ['Date', 'DateTime']),
        ];
    }
}
```

Переопределять `getFormat()` не требуется: `AbstractDescriptor` уже возвращает `Format::JSON_LD`.

Имена свойств и допустимые типы должны соответствовать спецификации создаваемого типа Schema.org. Машинное имя
дескриптора рекомендуется выбирать равным имени типа в Schema.org.

## Пример социальной мета-разметки

Дескриптор социальной мета-разметки должен переопределить формат и указать имя метатега для каждого свойства:

```php
<?php

namespace Vendor\Plugin\Microschema\Library\Metadata\Social;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;
use Joomla\Component\Microschema\Administrator\Metadata\Format;

final class LibrarySocial extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'LibrarySocial';
    }

    public function getFormat(): Format
    {
        return Format::META;
    }

    public function getProperties(): array
    {
        return [
            $this->property('title', required: true, tag: 'library:title'),
            $this->property('description', tag: 'library:description'),
            $this->property('image', ['URL'], tag: 'library:image'),
        ];
    }
}
```

Теги `library:*` в примере условные. Используйте имена тегов, определённые фактическим протоколом социальной
мета-разметки.

После регистрации новый социальный тип автоматически появится в настройках `com_microschema`. Значение его
переключателя сохраняется под машинным именем дескриптора, например `socials.LibrarySocial`.

## Регистрация дескрипторов

Дескрипторы регистрируются Joomla-плагином через событие `RegisterMetadataEvent`. Плагин должен реализовывать
`Joomla\Event\SubscriberInterface`:

```php
<?php

namespace Vendor\Plugin\Microschema\Library\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Microschema\Administrator\Event\RegisterMetadataEvent;
use Joomla\Event\SubscriberInterface;
use Vendor\Plugin\Microschema\Library\Metadata\SchemaOrg\Book;
use Vendor\Plugin\Microschema\Library\Metadata\Social\LibrarySocial;

final class Library extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RegisterMetadataEvent::NAME => 'registerMetadata',
        ];
    }

    public function registerMetadata(RegisterMetadataEvent $event): void
    {
        $event->registerSchemaOrg(Book::class);
        $event->registerSocial(LibrarySocial::class);
    }
}
```

`registerSchemaOrg()` принимает имя класса Schema.org-дескриптора, а `registerSocial()` — имя класса дескриптора
социальной мета-разметки. Один обработчик может зарегистрировать любое количество типов.

Использование `RegisterMetadataEvent::NAME` предпочтительнее строкового литерала: опечатка в имени события в этом
случае обнаруживается проще.

Имена дескрипторов должны быть уникальны внутри соответствующего реестра. Если два разных класса возвращают одно
имя, `MetadataRegistry` выбросит `LogicException`. Повторная регистрация того же класса допустима.

## Поставщик служб плагина

Точка входа плагина регистрируется в `services/provider.php`:

```php
<?php

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Vendor\Plugin\Microschema\Library\Extension\Library;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            static function (Container $container): PluginInterface {
                $plugin = new Library(
                    (array) PluginHelper::getPlugin('microschema', 'library')
                );
                $plugin->setDispatcher($container->get(DispatcherInterface::class));
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
```

Компонент загружает включённые плагины группы `microschema` в отдельный диспетчер до отправки событий регистрации.
Подписки этих плагинов регистрируются в диспетчере событий MicroSchema, а не в общей системной шине Joomla.

## Расширение административных форм

Системный плагин передаёт подготовку любой формы административной части плагинам группы `microschema` через
событие `PrepareFormEvent`. Системный плагин не привязан к конкретному компоненту и сам не добавляет вкладки:
каждый интеграционный плагин проверяет контекст и изменяет только поддерживаемые им формы.

```php
<?php

namespace Vendor\Plugin\Microschema\Library\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Microschema\Administrator\Event\PrepareFormEvent;
use Joomla\Event\SubscriberInterface;

final class Library extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PrepareFormEvent::NAME => 'prepareForm',
        ];
    }

    public function prepareForm(PrepareFormEvent $event): void
    {
        if ($event->getContext() !== 'com_example.item') {
            return;
        }

        $form = $event->getForm();
        $data = $event->getData();

        // Интеграционный плагин добавляет свои fieldset или вкладки через API Form.
    }
}
```

`getContext()` возвращает имя Joomla-формы (`$form->getName()`), `getForm()` — изменяемый экземпляр формы,
а `getData()` — переданные моделью данные. Событие отправляется только в административной части. Один плагин может
обслуживать несколько контекстов и добавлять для каждого из них собственный набор вкладок.

## Манифест и структура плагина

Минимальная структура плагина:

```text
plg_microschema_library/
├── library.xml
├── services/
│   └── provider.php
└── src/
    ├── Extension/
    │   └── Library.php
    └── Metadata/
        ├── SchemaOrg/
        │   └── Book.php
        └── Social/
            └── LibrarySocial.php
```

Манифест `library.xml`:

```xml
<?xml version="1.0" encoding="utf-8"?>
<extension type="plugin" group="microschema" method="upgrade">
    <name>plg_microschema_library</name>
    <version>1.0.0</version>
    <description>Дополнительные типы метаданных для com_microschema</description>
    <namespace path="src">Vendor\Plugin\Microschema\Library</namespace>
    <files>
        <folder plugin="library">services</folder>
        <folder>src</folder>
    </files>
</extension>
```

После установки плагин необходимо включить. Отключённый плагин не будет загружен и его дескрипторы не попадут в
реестр.

## Использование в `params`

Машинное имя Schema.org-дескриптора сохраняется в `schemaOrg.type`. Свойства дескриптора определяют допустимые
ключи объекта `schemaOrg.properties`:

```json
{
  "version": 1,
  "schemaOrg": {
    "enabled": true,
    "type": "Book",
    "properties": {
      "name": {
        "source": "field",
        "value": "title"
      },
      "isbn": {
        "source": "customField",
        "value": "book_isbn"
      }
    }
  }
}
```

Для социальной мета-разметки значение переключателя хранится по имени дескриптора:

```json
{
  "socials": {
    "LibrarySocial": 1
  }
}
```

Значения свойств получают зарегистрированные источники данных. Дескриптор определяет допустимую структуру, но не
должен самостоятельно читать материал, выполнять запросы к базе или преобразовывать значения.

Во время сбора интеграционный плагин добавляет фактические значения социальной разметки отдельно от Schema.org:

```php
$event->addSocialMetadata(
    contextKey: $article->getKey(),
    uid: 'social.article',
    data: [
        '@type' => 'OpenGraph',
        'title' => 'Заголовок материала',
        'description' => 'Описание материала',
        'image' => ['https://example.test/image.jpg'],
    ],
);
```

Кандидаты с одинаковым `uid` объединяются по тому же пути контекстов и правилам приоритетов, что и Schema.org,
но хранятся в отдельном реестре. Рендерер использует `tag` из `PropertyDefinition`, выводит `og:*` и `fb:*` через
атрибут `property`, остальные протоколы — через `name`.

## Рекомендации

- Используйте стабильное машинное имя дескриптора: его изменение нарушит существующие конфигурации.
- Не регистрируйте разные классы с одинаковым `getName()` внутри одной категории.
- Используйте `AbstractDescriptor`, если тип не требует собственного механизма создания `PropertyDefinition`.
- Указывайте `required: true` только для свойств, без которых разметка соответствующего типа недействительна.
- Указывайте `multiple: true` только для свойств, действительно допускающих список значений.
- Для Schema.org используйте имена и типы из официальной спецификации.
- Для формата `META` указывайте полный атрибут тега в `tag` и возвращайте `Format::META`.
- Не загружайте данные и не выполняйте побочные действия в конструкторе дескриптора.
- Не добавляйте контекстную бизнес-логику в `getProperties()`: один дескриптор должен стабильно описывать один тип.
- Регистрируйте интеграционные типы плагином группы `microschema`, не изменяя встроенный системный плагин компонента.
