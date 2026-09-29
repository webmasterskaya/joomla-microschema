<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\Social;

/**
 * VK link previews consume Open Graph metadata; no separate VK vocabulary is invented here.
 */
final class Vk extends OpenGraph
{
    public function getName(): string
    {
        return 'VK';
    }
}
