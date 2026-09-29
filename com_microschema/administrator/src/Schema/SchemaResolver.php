<?php

namespace Joomla\Component\Microschema\Administrator\Schema;

final class SchemaResolver
{
    /**
     * @return array<string, array<string, mixed>> resolved schemas indexed by UID
     */
    public function resolve(ContextPath $path, SchemaRegistry $registry): array
    {
        $resolved = [];

        foreach ($path->all() as $node) {
            $candidates = $registry->forContext($node);

            usort(
                $candidates,
                static fn (array $left, array $right): int => [
                    $left['candidate']->priority,
                    $left['sequence'],
                ] <=> [
                    $right['candidate']->priority,
                    $right['sequence'],
                ],
            );

            foreach ($candidates as $entry) {
                $candidate = $entry['candidate'];
                $resolved[$candidate->uid] ??= [];

                foreach ($candidate->data as $name => $value) {
                    if ($value !== null) {
                        $resolved[$candidate->uid][$name] = $value;
                    }
                }
            }
        }

        return $resolved;
    }
}
