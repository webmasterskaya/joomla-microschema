<?php

namespace Joomla\Component\Microschema\Administrator\Schema;

final class SchemaRegistry
{
    /**
     * @var array<string, list<array{candidate: SchemaCandidate, sequence: int}>>
     */
    private array $candidates = [];

    private int $sequence = 0;

    public function add(ContextNode|string $context, SchemaCandidate $candidate): self
    {
        $key = $context instanceof ContextNode ? $context->getKey() : $context;

        $this->candidates[$key][] = [
            'candidate' => $candidate,
            'sequence' => $this->sequence++,
        ];

        return $this;
    }

    /**
     * @return list<array{candidate: SchemaCandidate, sequence: int}>
     */
    public function forContext(ContextNode|string $context): array
    {
        $key = $context instanceof ContextNode ? $context->getKey() : $context;

        return $this->candidates[$key] ?? [];
    }
}
