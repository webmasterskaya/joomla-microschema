<?php

namespace Joomla\Component\Microschema\Administrator\Metadata;

final class SocialMetadataRenderer
{
    /**
     * @param array<string, array<string, mixed>>              $metadata
     * @param array<string, class-string<DescriptorInterface>> $descriptors
     * @param array<string, mixed>                             $enabledTypes
     *
     * @return list<string>
     */
    public function render(array $metadata, array $descriptors, array $enabledTypes): array
    {
        $tags = [];

        foreach ($metadata as $data) {
            $type = trim((string) ($data['@type'] ?? ''));

            if ($type === '' || empty($enabledTypes[$type]) || !isset($descriptors[$type])) {
                continue;
            }

            $descriptorClass = $descriptors[$type];
            $descriptor = new $descriptorClass();

            if ($descriptor->getFormat() !== Format::META) {
                continue;
            }

            foreach ($descriptor->getProperties() as $property) {
                if ($property->tag === null || !array_key_exists($property->name, $data)) {
                    continue;
                }

                foreach ($this->normalizeValues($data[$property->name]) as $value) {
                    $tags[] = $this->renderTag($property->tag, $value);
                }
            }
        }

        return $tags;
    }

    /** @return list<string> */
    private function normalizeValues(mixed $value): array
    {
        $values = is_array($value) ? $value : [$value];
        $normalized = [];

        foreach ($values as $item) {
            if ($item === null || is_array($item) || (is_object($item) && !$item instanceof \Stringable)) {
                continue;
            }

            if (is_bool($item)) {
                $normalized[] = $item ? '1' : '0';
                continue;
            }

            $normalized[] = (string) $item;
        }

        return $normalized;
    }

    private function renderTag(string $name, string $content): string
    {
        $attribute = preg_match('/^(?:og|fb):/i', $name) === 1 ? 'property' : 'name';

        return sprintf(
            '<meta %s="%s" content="%s">',
            $attribute,
            htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'),
            htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'),
        );
    }
}
