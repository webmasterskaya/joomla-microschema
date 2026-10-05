<?php

namespace Joomla\Component\Microschema\Administrator\Schema;

final class JsonLdRenderer
{
    private const CONTEXT = 'https://schema.org';

    /**
     * @param array<string, array<string, mixed>> $schemas resolved schemas indexed by UID
     *
     * @throws \JsonException
     *
     * @return list<string>
     */
    public function render(array $schemas): array
    {
        if ($schemas === []) {
            return [];
        }

        $graph = [];

        foreach ($schemas as $schema) {
            if (($schema['@context'] ?? null) === self::CONTEXT) {
                unset($schema['@context']);
            }

            $graph[] = $schema;
        }

        $json = json_encode(
            [
                '@context' => self::CONTEXT,
                '@graph' => $graph,
            ],
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT,
        );

        return ['<script type="application/ld+json">'.$json.'</script>'];
    }
}
