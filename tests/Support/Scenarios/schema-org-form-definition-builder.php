<?php

declare(strict_types=1);

namespace Joomla\Registry {
    final class Registry
    {
        public function __construct(private readonly array $data = [])
        {
        }

        public function toArray(): array
        {
            return $this->data;
        }
    }
}

namespace Joomla\CMS\Language {
    final class Text
    {
        public static function _(string $key): string
        {
            return match ($key) {
                'TEST_ORDER_UNORDERED' => 'Unordered',
                'TEST_ORDER_ASCENDING' => 'Ascending',
                default => $key,
            };
        }
    }
}

namespace {
    use Joomla\Component\Microschema\Administrator\Form\SchemaOrgFormDefinitionBuilder;
    use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

    require_once __DIR__ . '/../../../com_microschema/administrator/src/Metadata/Format.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/Metadata/PropertyDefinition.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/Metadata/DescriptorInterface.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/Metadata/AbstractDescriptor.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/Form/SchemaOrgFormDefinitionBuilder.php';

    final class DefinitionArticle extends AbstractDescriptor
    {
        public function getName(): string
        {
            return 'Article';
        }

        public function getProperties(): array
        {
            return [
                $this->property('headline', required: true),
                $this->property('publisher', ['Organization', '@id']),
                $this->property('datePublished', ['DateTime']),
                $this->property('dateModified', ['Date']),
                $this->property('order', ['OrderType'], options: [
                    'https://example.test/Unordered' => 'TEST_ORDER_UNORDERED',
                    'https://example.test/Ascending' => 'TEST_ORDER_ASCENDING',
                ]),
                $this->property('inLanguage', automatic: true),
            ];
        }
    }

    final class DefinitionOrganization extends AbstractDescriptor
    {
        public function getName(): string
        {
            return 'Organization';
        }

        public function getProperties(): array
        {
            return [$this->property('name', required: true)];
        }
    }

    $builder = new SchemaOrgFormDefinitionBuilder([
        'Article'      => DefinitionArticle::class,
        'Organization' => DefinitionOrganization::class,
    ]);
    $definition = $builder->build(new \Joomla\Registry\Registry([
        'headline' => 'Example',
    ]));

    if (($definition['value']['headline'] ?? null) !== 'Example') {
        throw new RuntimeException('The current form value must be normalized.');
    }

    $publisher = $definition['schemas']['Article']['properties'][1] ?? null;

    if (($publisher['types'][0]['kind'] ?? null) !== 'object') {
        throw new RuntimeException('Registered Schema.org types must be exposed as objects.');
    }

    if (($publisher['types'][1]['kind'] ?? null) !== 'reference') {
        throw new RuntimeException('The @id type must be exposed as a reference.');
    }

    if (!isset($definition['schemas']['Organization'])) {
        throw new RuntimeException('Definitions must be a flat type map.');
    }

    foreach ($definition['schemas']['Article']['properties'] as $property) {
        if (($property['name'] ?? null) === 'inLanguage') {
            throw new RuntimeException('Automatically populated properties must not be exposed in forms.');
        }
    }

    $datePublished = $definition['schemas']['Article']['properties'][2] ?? null;

    if (($datePublished['types'][0]['input'] ?? null) !== 'calendar-datetime') {
        throw new RuntimeException('DateTime values must use the Joomla date and time calendar.');
    }

    $dateModified = $definition['schemas']['Article']['properties'][3] ?? null;

    if (($dateModified['types'][0]['input'] ?? null) !== 'calendar-date') {
        throw new RuntimeException('Date values must use the Joomla date calendar.');
    }

    $order = $definition['schemas']['Article']['properties'][4] ?? null;

    if (($order['description'] ?? null) !== 'OrderType') {
        throw new RuntimeException('Enumerated properties must expose their Schema.org type.');
    }

    if (($order['options'] ?? null) !== [
        ['value' => 'https://example.test/Unordered', 'label' => 'Unordered'],
        ['value' => 'https://example.test/Ascending', 'label' => 'Ascending'],
    ]) {
        throw new RuntimeException('Enumerated properties must expose their translated options.');
    }

    echo "Schema.org form definition builder tests passed.\n";
}
