<?php

namespace Joomla\Component\Microschema\Administrator\DataSource;

final readonly class DataSourceCatalogBuilder
{
    public const MAX_DEPTH = 4;

    public function __construct(
        private DataSourceRegistry $sourceRegistry,
        private DataTypeRegistry $typeRegistry,
    ) {
    }

    /**
     * @param list<string> $excludedSources
     *
     * @return array{sources: list<array<string, mixed>>}
     */
    public function build(DataContext $context, array $excludedSources = []): array
    {
        $sources = [];
        $excluded = array_fill_keys($excludedSources, true);

        foreach ($this->sourceRegistry->forContext($context->context) as $source) {
            if (isset($excluded[$source->getName()]) || !$this->typeRegistry->has($source->getType())) {
                continue;
            }

            $fields = $this->buildFields($source->getType(), $source->getValue($context), $context, 0);

            if ($fields === []) {
                continue;
            }

            $sources[] = [
                'name' => $source->getName(),
                'label' => $source->getLabel(),
                'fields' => $fields,
            ];
        }

        usort(
            $sources,
            static fn (array $left, array $right): int => strnatcasecmp($left['name'], $right['name']),
        );

        return ['sources' => $sources];
    }

    /** @return list<array<string, mixed>> */
    private function buildFields(string $typeName, mixed $value, DataContext $context, int $depth): array
    {
        if (!$this->typeRegistry->has($typeName)) {
            return [];
        }

        $type = $this->typeRegistry->get($typeName);
        $fields = [];

        foreach ($type->getFields($value, $context) as $field) {
            $object = $this->typeRegistry->has($field->type);

            if (!$object) {
                $fields[] = [
                    'name' => $field->name,
                    'label' => $field->label,
                    'type' => $field->type,
                    'object' => false,
                ];
                continue;
            }

            if ($depth >= self::MAX_DEPTH - 1) {
                continue;
            }

            $nestedValue = $value === null ? null : $type->resolve($value, $field->name, $context);
            $nestedFields = $this->buildFields($field->type, $nestedValue, $context, $depth + 1);

            if ($nestedFields === []) {
                continue;
            }

            $fields[] = [
                'name' => $field->name,
                'label' => $field->label,
                'type' => $field->type,
                'object' => true,
                'fields' => $nestedFields,
            ];
        }

        return $fields;
    }
}
