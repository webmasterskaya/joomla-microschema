<?php

namespace Joomla\Component\Microschema\Administrator\Schema;

final class ContextPath
{
    /** @var array<string, ContextNode> */
    private array $nodes = [];

    public function append(ContextNode $node): self
    {
        $this->assertUnique($node);
        $this->nodes[$node->getKey()] = $node;

        return $this;
    }

    public function insertAfter(string $targetKey, ContextNode $node): self
    {
        if (!isset($this->nodes[$targetKey])) {
            throw new \InvalidArgumentException(sprintf('Schema context "%s" is not in the active path.', $targetKey));
        }

        $this->assertUnique($node);

        $nodes = [];

        foreach ($this->nodes as $key => $currentNode) {
            $nodes[$key] = $currentNode;

            if ($key === $targetKey) {
                $nodes[$node->getKey()] = $node;
            }
        }

        $this->nodes = $nodes;

        return $this;
    }

    /** @return list<ContextNode> */
    public function all(): array
    {
        return array_values($this->nodes);
    }

    public function has(string $key): bool
    {
        return isset($this->nodes[$key]);
    }

    private function assertUnique(ContextNode $node): void
    {
        if ($this->has($node->getKey())) {
            throw new \LogicException(sprintf('Schema context "%s" is already in the active path.', $node->getKey()));
        }
    }
}
