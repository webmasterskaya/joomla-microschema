<?php

namespace Joomla\Component\Microschema\Administrator\Schema;

final class BreadcrumbListBuilder
{
    /**
     * @param list<array{name: string, url: string}> $entries
     *
     * @return array<string, mixed>
     */
    public function build(array $entries): array
    {
        $items = [];
        $seen = [];

        foreach ($entries as $entry) {
            $name = trim($entry['name']);
            $url = trim($entry['url']);

            if ($name === '' || $url === '' || isset($seen[$url])) {
                continue;
            }

            $seen[$url] = true;
            $items[] = [
                '@type' => 'ListItem',
                'position' => count($items) + 1,
                'name' => $name,
                'item' => $url,
            ];
        }

        if ($items === []) {
            return [];
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }
}
