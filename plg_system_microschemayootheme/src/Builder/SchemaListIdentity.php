<?php

namespace Joomla\Plugin\System\MicroschemaYootheme\Builder;

defined('_JEXEC') || exit;

final class SchemaListIdentity
{
    private \WeakMap $identifiers;
    private int $sequence = 0;

    public function __construct()
    {
        $this->identifiers = new \WeakMap();
    }

    public function resolve(object $node, string $identifier, string $pageUrl): string
    {
        $identifier = trim($identifier);
        $pageUrl = explode('#', $pageUrl, 2)[0];

        if ($identifier === '') {
            $this->identifiers[$node] ??= 'itemlist-'.++$this->sequence;
            $identifier = '#'.$this->identifiers[$node];
        }

        return str_starts_with($identifier, '#') ? $pageUrl.$identifier : $identifier;
    }
}
