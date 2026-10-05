<?php

namespace Joomla\Component\Microschema\Administrator\Schema;

use Joomla\CMS\Router\Route;
use Joomla\Component\Microschema\Administrator\Metadata\DescriptorInterface;
use Joomla\Component\Microschema\Administrator\Metadata\PropertyDefinition;

final class SchemaUrlEnricher
{
    /**
     * @param array<string, array<string, mixed>>              $schemas
     * @param array<string, class-string<DescriptorInterface>> $descriptors
     *
     * @return array<string, array<string, mixed>>
     */
    public function enrich(array $schemas, array $descriptors, string $siteUrl): array
    {
        $siteUrl = rtrim(trim($siteUrl), '/').'/';

        if ($schemas === [] || $siteUrl === '/') {
            return $schemas;
        }

        $urlProperties = $this->createUrlPropertyMap($descriptors);

        foreach ($schemas as $uid => $schema) {
            $schemas[$uid] = $this->enrichValue($schema, $urlProperties, $siteUrl);
        }

        return $schemas;
    }

    /**
     * @param array<string, class-string<DescriptorInterface>> $descriptors
     *
     * @return array<string, array<string, true>>
     */
    private function createUrlPropertyMap(array $descriptors): array
    {
        $map = [];

        foreach ($descriptors as $descriptorClass) {
            $descriptor = new $descriptorClass();

            foreach ($descriptor->getProperties() as $property) {
                if ($this->supportsUrl($property)) {
                    $map[$descriptor->getName()][$property->name] = true;
                }
            }
        }

        return $map;
    }

    private function supportsUrl(PropertyDefinition $property): bool
    {
        return in_array('URL', $property->types, true) || in_array('@id', $property->types, true);
    }

    /** @param array<string, array<string, true>> $urlProperties */
    private function enrichValue(
        mixed $value,
        array $urlProperties,
        string $siteUrl,
        bool $isUrl = false,
    ): mixed {
        if (is_string($value)) {
            return $isUrl ? $this->normalizeUrl($value, $siteUrl) : $value;
        }

        if (!is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            foreach ($value as $index => $item) {
                $value[$index] = $this->enrichValue($item, $urlProperties, $siteUrl, $isUrl);
            }

            return $value;
        }

        $types = $this->normalizeTypes($value['@type'] ?? null);

        foreach ($value as $property => $item) {
            $propertyIsUrl = $property === '@id' || $this->isUrlProperty($types, (string) $property, $urlProperties);
            $value[$property] = $this->enrichValue($item, $urlProperties, $siteUrl, $propertyIsUrl);
        }

        return $value;
    }

    /**
     * @param list<string>                       $types
     * @param array<string, array<string, true>> $urlProperties
     */
    private function isUrlProperty(array $types, string $property, array $urlProperties): bool
    {
        foreach ($types as $type) {
            if (isset($urlProperties[$type][$property])) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function normalizeTypes(mixed $types): array
    {
        if (is_string($types)) {
            return [$types];
        }

        if (!is_array($types)) {
            return [];
        }

        return array_values(array_filter($types, static fn (mixed $type): bool => is_string($type) && $type !== ''));
    }

    private function normalizeUrl(string $url, string $siteUrl): string
    {
        $url = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($url === '') {
            return '';
        }

        $parts = parse_url($siteUrl);

        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $origin = $parts['scheme'].'://'.$parts['host']
            .(isset($parts['port']) ? ':'.$parts['port'] : '');

        if (str_starts_with($url, '//')) {
            return $parts['scheme'].':'.$url;
        }

        $route = $url;

        if (str_starts_with($url, $siteUrl)) {
            $route = substr($url, strlen($siteUrl));
        } elseif (str_starts_with($url, '/')) {
            $basePath = rtrim($parts['path'] ?? '', '/').'/';
            $route = str_starts_with($url, $basePath) ? substr($url, strlen($basePath)) : $url;
        }

        if (preg_match('/^index\.php(?:[?#]|$)/', $route) === 1) {
            return Route::link('site', $route, false, Route::TLS_IGNORE, true);
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $url) === 1) {
            return $url;
        }

        return str_starts_with($url, '/') ? $origin.$url : $siteUrl.$url;
    }
}
