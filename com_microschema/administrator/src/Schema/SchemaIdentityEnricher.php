<?php

namespace Joomla\Component\Microschema\Administrator\Schema;

final class SchemaIdentityEnricher
{
    private int $itemListCount = 0;

    /**
     * @param array<string, array<string, mixed>> $schemas
     *
     * @return array<string, array<string, mixed>>
     */
    public function enrich(
        array $schemas,
        string $siteUrl,
        string $pageUrl,
        bool $breadcrumbEnabled = false,
    ): array {
        $siteUrl = $this->normalizeSiteUrl($siteUrl);
        $pageUrl = $this->normalizeResourceUrl($pageUrl);

        if ($schemas === [] || ($siteUrl === '' && $pageUrl === '')) {
            return $schemas;
        }

        $this->itemListCount = $this->countType($schemas, 'ItemList');

        foreach ($schemas as $uid => $schema) {
            $schemas[$uid] = $this->enrichValue($schema, $siteUrl, $pageUrl, (string) $uid, []);
        }

        if ($breadcrumbEnabled) {
            $breadcrumbId = $this->findTypeIdentifier($schemas, 'BreadcrumbList');

            if ($breadcrumbId !== '') {
                foreach ($schemas as $uid => $schema) {
                    $schemas[$uid] = $this->addBreadcrumbReference($schema, $breadcrumbId);
                }
            }
        }

        return $schemas;
    }

    /** @param list<string|int> $path */
    private function enrichValue(
        mixed $value,
        string $siteUrl,
        string $pageUrl,
        string $uid,
        array $path,
    ): mixed {
        if (!is_array($value)) {
            return $value;
        }

        $identifier = trim((string) ($value['@id'] ?? ''));

        if ($identifier === '') {
            $identifier = $this->createIdentifier($value, $siteUrl, $pageUrl, $uid, $path);

            if ($identifier !== '') {
                $value = $this->insertIdentifier($value, $identifier);
            }
        }

        foreach ($value as $name => $item) {
            $value[$name] = $this->enrichValue($item, $siteUrl, $pageUrl, $uid, [...$path, $name]);
        }

        return $value;
    }

    /**
     * @param array<string|int, mixed> $value
     * @param list<string|int>         $path
     */
    private function createIdentifier(
        array $value,
        string $siteUrl,
        string $pageUrl,
        string $uid,
        array $path,
    ): string {
        $types = $this->normalizeTypes($value['@type'] ?? null);

        if (in_array('Organization', $types, true)) {
            return $siteUrl === '' ? '' : $siteUrl.'#organization';
        }

        if (in_array('WebSite', $types, true)) {
            return $siteUrl === '' ? '' : $siteUrl.'#website';
        }

        if ($this->hasType($types, ['WebPage', 'CollectionPage'])) {
            $url = $this->schemaUrl($value) ?: $pageUrl;

            return $url === '' ? '' : $url.'#webpage';
        }

        if (in_array('BreadcrumbList', $types, true)) {
            return $pageUrl === '' ? '' : $pageUrl.'#breadcrumb';
        }

        if ($this->hasType($types, ['Article', 'NewsArticle', 'BlogPosting'])) {
            $url = $this->schemaUrl($value) ?: $pageUrl;

            return $url === '' ? '' : $url.'#article';
        }

        if (in_array('ItemList', $types, true) && $pageUrl !== '') {
            if ($this->itemListCount === 1) {
                return $pageUrl.'#itemlist';
            }

            $suffix = $this->identifierSuffix($uid, $path);

            return $pageUrl.'#itemlist-'.$suffix;
        }

        if (in_array('ImageObject', $types, true)) {
            $property = (string) ($path[array_key_last($path)] ?? '');

            if ($property === 'logo' && $siteUrl !== '') {
                return $siteUrl.'#logo';
            }

            return $this->schemaUrl($value, ['url', 'contentUrl']);
        }

        return '';
    }

    /** @param array<string|int, mixed> $value */
    private function insertIdentifier(array $value, string $identifier): array
    {
        $result = [];

        foreach ($value as $name => $item) {
            $result[$name] = $item;

            if ($name === '@type') {
                $result['@id'] = $identifier;
            }
        }

        return isset($result['@id']) ? $result : ['@id' => $identifier] + $result;
    }

    /**
     * @param array<string|int, mixed> $value
     * @param list<string>             $properties
     */
    private function schemaUrl(array $value, array $properties = ['url']): string
    {
        foreach ($properties as $property) {
            if (is_string($value[$property] ?? null)) {
                $url = $this->normalizeResourceUrl($value[$property]);

                if ($url !== '') {
                    return $url;
                }
            }
        }

        return '';
    }

    /** @param list<string|int> $path */
    private function identifierSuffix(string $uid, array $path): string
    {
        $value = strtolower($uid.'-'.implode('-', array_map('strval', $path)));
        $suffix = trim((string) preg_replace('/[^a-z0-9]+/', '-', $value), '-');

        return $suffix !== '' ? $suffix : substr(hash('sha256', $value), 0, 12);
    }

    private function normalizeSiteUrl(string $url): string
    {
        $url = $this->normalizeResourceUrl($url);

        return $url === '' ? '' : rtrim($url, '/').'/';
    }

    private function normalizeResourceUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        return (string) preg_replace('/#.*$/', '', $url);
    }

    /** @return list<string> */
    private function normalizeTypes(mixed $types): array
    {
        if (is_string($types)) {
            $types = [$types];
        }

        if (!is_array($types)) {
            return [];
        }

        return array_values(array_filter($types, static fn (mixed $type): bool => is_string($type) && $type !== ''));
    }

    /**
     * @param list<string> $types
     * @param list<string> $expected
     */
    private function hasType(array $types, array $expected): bool
    {
        return array_intersect($types, $expected) !== [];
    }

    private function countType(mixed $value, string $expected): int
    {
        if (!is_array($value)) {
            return 0;
        }

        $count = in_array($expected, $this->normalizeTypes($value['@type'] ?? null), true) ? 1 : 0;

        foreach ($value as $item) {
            $count += $this->countType($item, $expected);
        }

        return $count;
    }

    private function findTypeIdentifier(mixed $value, string $expected): string
    {
        if (!is_array($value)) {
            return '';
        }

        if (in_array($expected, $this->normalizeTypes($value['@type'] ?? null), true)) {
            $identifier = trim((string) ($value['@id'] ?? ''));

            if ($identifier !== '') {
                return $identifier;
            }
        }

        foreach ($value as $item) {
            $identifier = $this->findTypeIdentifier($item, $expected);

            if ($identifier !== '') {
                return $identifier;
            }
        }

        return '';
    }

    private function addBreadcrumbReference(mixed $value, string $breadcrumbId): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $types = $this->normalizeTypes($value['@type'] ?? null);

        if (
            $this->hasType($types, ['WebPage', 'CollectionPage'])
            && !array_key_exists('breadcrumb', $value)
        ) {
            $value['breadcrumb'] = ['@id' => $breadcrumbId];
        }

        foreach ($value as $name => $item) {
            $value[$name] = $this->addBreadcrumbReference($item, $breadcrumbId);
        }

        return $value;
    }
}
