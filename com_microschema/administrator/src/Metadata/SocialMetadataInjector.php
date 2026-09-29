<?php

namespace Joomla\Component\Microschema\Administrator\Metadata;

final class SocialMetadataInjector
{
    private const START_MARKER = '<!-- MicroSchema Social: start -->';

    private const END_MARKER = '<!-- MicroSchema Social: end -->';

    /** @param list<string> $tags */
    public function inject(string $body, array $tags): string
    {
        if ($tags === [] || !str_contains($body, '</head>')) {
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

        return str_replace('</head>', $markup.PHP_EOL.'</head>', $body);
    }
}
