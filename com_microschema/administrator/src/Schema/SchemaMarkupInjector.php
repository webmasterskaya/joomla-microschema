<?php

namespace Joomla\Component\Microschema\Administrator\Schema;

final class SchemaMarkupInjector
{
    public const POSITION_HEAD = 'head';

    public const POSITION_BODY = 'body';

    private const START_MARKER = '<!-- MicroSchema: start -->';

    private const END_MARKER = '<!-- MicroSchema: end -->';

    /** @param list<string> $tags */
    public function inject(string $body, array $tags, string $position = self::POSITION_HEAD): string
    {
        if ($tags === []) {
            return $body;
        }

        $closingTag = $position === self::POSITION_BODY ? '</body>' : '</head>';

        if (!str_contains($body, $closingTag)) {
            return $body;
        }

        $markup = implode(
            PHP_EOL,
            [
                self::START_MARKER,
                ...$tags,
                self::END_MARKER,
            ],
        );

        return str_replace(
            $closingTag,
            $markup.PHP_EOL.$closingTag,
            $body,
        );
    }
}
