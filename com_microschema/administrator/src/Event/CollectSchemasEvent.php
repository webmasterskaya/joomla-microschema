<?php

namespace Joomla\Component\Microschema\Administrator\Event;

use Joomla\CMS\Event\AbstractEvent;
use Joomla\Component\Microschema\Administrator\Schema\ContextNode;
use Joomla\Component\Microschema\Administrator\Schema\ContextPath;
use Joomla\Component\Microschema\Administrator\Schema\SchemaCandidate;
use Joomla\Component\Microschema\Administrator\Schema\SchemaCollector;
use Joomla\Component\Microschema\Administrator\Schema\SchemaRegistry;

final class CollectSchemasEvent extends AbstractEvent
{
    public const NAME = 'onMicroschemaCollectSchemas';

    public function __construct(private readonly SchemaCollector $collector)
    {
        parent::__construct(self::NAME);
    }

    public function appendContext(string $context, int|string $id): ContextNode
    {
        return $this->collector->appendContext($context, $id);
    }

    public function addMenuOverride(string $targetKey, int $menuItemId): ContextNode
    {
        return $this->collector->addMenuOverride($targetKey, $menuItemId);
    }

    /** @param array<string, mixed> $data */
    public function addSchema(string $contextKey, string $uid, array $data, int $priority = 0): SchemaCandidate
    {
        return $this->collector->addSchema($uid, $data, $priority, $contextKey);
    }

    /** @param array<string, mixed> $data */
    public function addSocialMetadata(string $contextKey, string $uid, array $data, int $priority = 0): SchemaCandidate
    {
        return $this->collector->addSocialMetadata($uid, $data, $priority, $contextKey);
    }

    public function getPath(): ContextPath
    {
        return $this->collector->getPath();
    }

    public function getRegistry(): SchemaRegistry
    {
        return $this->collector->getRegistry();
    }

    public function getSocialRegistry(): SchemaRegistry
    {
        return $this->collector->getSocialRegistry();
    }
}
