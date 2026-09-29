<?php

namespace Joomla\Component\Microschema\Administrator\Schema;

final class ExistingSchemaChecker
{
    /** @param \Closure(string): void|null $debugLogger */
    public function __construct(private readonly ?\Closure $debugLogger = null)
    {
    }

    /**
     * @param array<string, array<string, mixed>> $schemas
     *
     * @return array<string, array<string, mixed>>
     */
    public function filter(string $body, array $schemas): array
    {
        if ($schemas === []) {
            return [];
        }

        $existingTypes = $this->findExistingTypes($body);

        if ($existingTypes === []) {
            return $schemas;
        }

        foreach ($schemas as $uid => $schema) {
            foreach ($this->normalizeTypes($schema['@type'] ?? null) as $type) {
                if (!isset($existingTypes[$type])) {
                    continue;
                }

                unset($schemas[$uid]);
                $this->debug(
                    sprintf(
                        'Skipped generated schema "%s" (%s): matching %s already exists.',
                        $uid,
                        $type,
                        $existingTypes[$type],
                    ),
                );
                break;
            }
        }

        return $schemas;
    }

    /** @return array<string, string> Schema type indexed by type, with its detected format as value. */
    private function findExistingTypes(string $body): array
    {
        $types = $this->findJsonLdTypes($body);

        foreach ($this->findMicrodataTypes($body) as $type) {
            $types[$type] ??= 'microdata';
        }

        return $types;
    }

    /** @return array<string, string> */
    private function findJsonLdTypes(string $body): array
    {
        $types = [];
        $match = preg_match_all(
            '~<script\b(?=[^>]*\btype\s*=\s*(?:["\']application/ld\+json["\']|application/ld\+json\b))[^>]*>(.*?)</script\s*>~is',
            $body,
            $matches,
        );

        if ($match === false || $match === 0) {
            return $types;
        }

        foreach ($matches[1] as $json) {
            try {
                $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                continue;
            }

            foreach ($this->collectSchemaOrgTypes($data) as $type) {
                $types[$type] = 'JSON-LD';
            }
        }

        return $types;
    }

    /** @return list<string> */
    private function findMicrodataTypes(string $body): array
    {
        $types = [];
        $match = preg_match_all(
            '~\bitemtype\s*=\s*(?:(["\'])(.*?)\1|([^\s>]+))~is',
            $body,
            $matches,
            PREG_SET_ORDER,
        );

        if ($match === false || $match === 0) {
            return $types;
        }

        foreach ($matches as $attributes) {
            $value = $attributes[2] !== '' ? $attributes[2] : ($attributes[3] ?? '');

            foreach (preg_split('/\s+/', trim($value)) ?: [] as $itemType) {
                if (preg_match('~^https?://(?:www\.)?schema\.org/([^/#?]+)~i', $itemType, $type) === 1) {
                    $types[] = $type[1];
                }
            }
        }

        return array_values(array_unique($types));
    }

    private function containsSchemaOrgUrl(mixed $context): bool
    {
        if (is_string($context)) {
            return preg_match('~^https?://(?:www\.)?schema\.org/?$~i', trim($context)) === 1;
        }

        if (!is_array($context)) {
            return false;
        }

        foreach ($context as $value) {
            if ($this->containsSchemaOrgUrl($value)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function collectSchemaOrgTypes(mixed $data, bool $inheritsSchemaContext = false): array
    {
        if (!is_array($data)) {
            return [];
        }

        $hasSchemaContext = array_key_exists('@context', $data)
            ? $this->containsSchemaOrgUrl($data['@context'])
            : $inheritsSchemaContext;
        $types = $hasSchemaContext ? $this->normalizeTypes($data['@type'] ?? null) : [];

        foreach ($data as $value) {
            if (is_array($value)) {
                $types = [...$types, ...$this->collectSchemaOrgTypes($value, $hasSchemaContext)];
            }
        }

        return array_values(array_unique($types));
    }

    /** @return list<string> */
    private function normalizeTypes(mixed $types): array
    {
        if (is_string($types) && $types !== '') {
            return [$types];
        }

        if (!is_array($types)) {
            return [];
        }

        return array_values(
            array_filter(
                $types,
                static fn (mixed $type): bool => is_string($type) && $type !== '',
            ),
        );
    }

    private function debug(string $message): void
    {
        if ($this->debugLogger !== null) {
            ($this->debugLogger)($message);
        }
    }
}
