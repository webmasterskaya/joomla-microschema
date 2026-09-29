<?php

namespace Joomla\Component\Microschema\Administrator\Schema;

final class SchemaCollector
{
    public const ROOT_CONTEXT = 'microschema.page';

    public const ROOT_ID = 'root';

    public const ROOT_KEY = self::ROOT_CONTEXT.':'.self::ROOT_ID;

    private readonly ContextPath $path;

    private readonly SchemaRegistry $registry;

    private readonly SchemaRegistry $socialRegistry;

    public function __construct()
    {
        $this->path = new ContextPath();
        $this->registry = new SchemaRegistry();
        $this->socialRegistry = new SchemaRegistry();

        $this->path->append(new ContextNode(self::ROOT_CONTEXT, self::ROOT_ID));
    }

    public function appendContext(string $context, int|string $id): ContextNode
    {
        $node = new ContextNode($context, $id);
        $this->path->append($node);

        return $node;
    }

    public function addMenuOverride(string $targetKey, int $menuItemId): ContextNode
    {
        $node = new ContextNode('com_menus.item', $menuItemId);
        $this->path->insertAfter($targetKey, $node);

        return $node;
    }

    /** @param array<string, mixed> $data */
    public function addSchema(
        string $uid,
        array $data,
        int $priority = 0,
        ?string $contextKey = null,
    ): SchemaCandidate {
        $candidate = new SchemaCandidate($uid, $data, $priority);
        $this->registry->add($contextKey ?? self::ROOT_KEY, $candidate);

        return $candidate;
    }

    public function getPath(): ContextPath
    {
        return $this->path;
    }

    public function getRegistry(): SchemaRegistry
    {
        return $this->registry;
    }

    /** @param array<string, mixed> $data */
    public function addSocialMetadata(
        string $uid,
        array $data,
        int $priority = 0,
        ?string $contextKey = null,
    ): SchemaCandidate {
        $candidate = new SchemaCandidate($uid, $data, $priority);
        $this->socialRegistry->add($contextKey ?? self::ROOT_KEY, $candidate);

        return $candidate;
    }

    public function getSocialRegistry(): SchemaRegistry
    {
        return $this->socialRegistry;
    }
}
