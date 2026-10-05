<?php

declare(strict_types=1);

namespace Joomla\CMS\Form {
    use SimpleXMLElement;

    class Form
    {
    }

    class FormField
    {
        protected SimpleXMLElement $xml;

        public function __construct(protected Form $form)
        {
        }

        public function setup(SimpleXMLElement $xml, mixed $value, string $group): bool
        {
            $this->xml = $xml;

            return true;
        }

        public function renderField(): string
        {
            return (string) $this->xml->asXML();
        }
    }
}

namespace Joomla\CMS\Form\Field {
    class SubformField extends \Joomla\CMS\Form\FormField
    {
    }

    class TextField extends \Joomla\CMS\Form\FormField
    {
    }
}

namespace {
    use Joomla\CMS\Form\Form;
    use Joomla\Component\Microschema\Administrator\Field\SchemaOrgFormBuilder;
    use Joomla\Component\Microschema\Administrator\Metadata\DescriptorInterface;
    use Joomla\Component\Microschema\Administrator\Metadata\Format;
    use Joomla\Component\Microschema\Administrator\Metadata\PropertyDefinition;

    require_once __DIR__ . '/../../../com_microschema/administrator/src/Metadata/Format.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/Metadata/PropertyDefinition.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/Metadata/DescriptorInterface.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/Field/SchemaOrgFormBuilder.php';

    $descriptor = new class () implements DescriptorInterface {
        public function getName(): string
        {
            return 'Example';
        }

        public function getFormat(): Format
        {
            return Format::JSON_LD;
        }

        public function getProperties(): array
        {
            return [new PropertyDefinition('value', ['Text', 'URL', '@id'])];
        }
    };

    $builder = new SchemaOrgFormBuilder(new Form(), [], 'article.reload');
    $html    = $builder->renderDescriptor($descriptor, null, 'params.schema_properties', 0, ['Example']);

    if (!str_contains($html, 'article.reload')) {
        throw new RuntimeException('A generated type selector must use the configured reload task.');
    }

    if (!str_contains($html, 'reloadtask="article.reload"')) {
        throw new RuntimeException('The reload task must be passed to nested Schema.org fields.');
    }

    if (!str_contains($html, '<option value="@id">@id</option>')) {
        throw new RuntimeException('A generated type selector must expose the JSON-LD @id reference type.');
    }

    try {
        new SchemaOrgFormBuilder(new Form(), [], "article.reload'); alert('unsafe");
        throw new RuntimeException('An invalid reload task must be rejected.');
    } catch (InvalidArgumentException) {
    }

    echo "Schema.org form builder reload tests passed.\n";
}
