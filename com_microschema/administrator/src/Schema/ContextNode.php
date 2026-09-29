<?php

namespace Joomla\Component\Microschema\Administrator\Schema;

final readonly class ContextNode
{
    public string $context;

    public int|string $id;

    public function __construct(string $context, int|string $id)
    {
        $context = trim($context);

        if ($context === '') {
            throw new \InvalidArgumentException('The schema context must not be empty.');
        }

        if ((is_int($id) && $id < 1) || (is_string($id) && trim($id) === '')) {
            throw new \InvalidArgumentException('The schema context ID must not be empty.');
        }

        $this->context = $context;
        $this->id = is_string($id) ? trim($id) : $id;
    }

    public function getKey(): string
    {
        return $this->context.':'.$this->id;
    }
}
