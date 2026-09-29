<?php

namespace Joomla\Component\Microschema\Administrator\Metadata;

final class ExistingSocialMetadataChecker
{
    /** @param \Closure(string): void|null $debugLogger */
    public function __construct(private readonly ?\Closure $debugLogger = null)
    {
    }

    /**
     * @param list<string> $tags
     *
     * @return list<string>
     */
    public function filter(string $body, array $tags): array
    {
        if ($tags === []) {
            return [];
        }

        $existingNames = $this->findMetadataNames($body);

        if ($existingNames === []) {
            return $tags;
        }

        $filteredTags = [];
        $loggedNames = [];

        foreach ($tags as $tag) {
            $matchingName = null;

            foreach ($this->findMetadataNames($tag) as $normalizedName => $name) {
                if (isset($existingNames[$normalizedName])) {
                    $matchingName = $name;
                    break;
                }
            }

            if ($matchingName === null) {
                $filteredTags[] = $tag;
                continue;
            }

            $normalizedName = strtolower($matchingName);

            if (!isset($loggedNames[$normalizedName])) {
                $this->debug(
                    sprintf(
                        'Skipped generated social metadata "%s": matching meta tag already exists.',
                        $matchingName,
                    ),
                );
                $loggedNames[$normalizedName] = true;
            }
        }

        return $filteredTags;
    }

    /** @return array<string, string> Normalized metadata name indexed by itself, original value as value. */
    private function findMetadataNames(string $html): array
    {
        $names = [];
        $match = preg_match_all('~<meta\b[^>]*>~i', $html, $tags);

        if ($match === false || $match === 0) {
            return $names;
        }

        foreach ($tags[0] as $tag) {
            $attributeMatch = preg_match_all(
                '~\b(?:name|property)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))~i',
                $tag,
                $attributes,
                PREG_SET_ORDER,
            );

            if ($attributeMatch === false || $attributeMatch === 0) {
                continue;
            }

            foreach ($attributes as $attribute) {
                $name = $attribute[1] !== ''
                    ? $attribute[1]
                    : ($attribute[2] !== '' ? $attribute[2] : ($attribute[3] ?? ''));
                $name = trim(html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if ($name !== '') {
                    $names[strtolower($name)] = $name;
                }
            }
        }

        return $names;
    }

    private function debug(string $message): void
    {
        if ($this->debugLogger !== null) {
            ($this->debugLogger)($message);
        }
    }
}
