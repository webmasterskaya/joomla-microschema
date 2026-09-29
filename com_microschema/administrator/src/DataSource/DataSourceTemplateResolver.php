<?php

namespace Joomla\Component\Microschema\Administrator\DataSource;

use Joomla\CMS\Language\Text;
use Joomla\Component\Microschema\Administrator\DataCollection\CollectionIteration;
use Joomla\Component\Microschema\Administrator\DataCollection\DataCollectionRegistry;

final readonly class DataSourceTemplateResolver
{
    private const COLLECTION_MAX_DEPTH = 8;

    private const PLACEHOLDER_PATTERN = '/\{([A-Za-z][A-Za-z0-9_-]*)\.([A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*)\}/';

    public function __construct(
        private DataSourceRegistry $sourceRegistry,
        private DataTypeRegistry $typeRegistry,
        private DataSourceCatalogBuilder $catalogBuilder,
        private DataCollectionRegistry $collectionRegistry,
        private ?\Closure $debugLogger = null,
    ) {
    }

    public function resolve(mixed $value, DataContext $context): mixed
    {
        return $this->resolveValue($value, $context, [], 0);
    }

    /** @param list<string> $collectionStack */
    private function resolveValue(mixed $value, DataContext $context, array $collectionStack, int $depth): mixed
    {
        if (is_string($value)) {
            return $this->resolveString($value, $context);
        }

        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        if (!is_array($value)) {
            return $value;
        }

        $resolved = [];
        $list = array_is_list($value);

        foreach ($value as $key => $item) {
            if ($this->isCollectionTemplate($item)) {
                $items = $this->resolveCollectionTemplate($item, $context, $collectionStack, $depth);

                if ($list) {
                    array_push($resolved, ...$items);
                } else {
                    $resolved[$key] = $items;
                }

                continue;
            }

            $resolved[$key] = $this->resolveValue($item, $context, $collectionStack, $depth);
        }

        return $resolved;
    }

    /**
     * @return array{sources: list<array<string, mixed>>, collections: list<array<string, mixed>>}
     */
    public function getCatalog(DataContext $context, array $excludedSources = []): array
    {
        $catalog = $this->buildSourceCatalog($context, $excludedSources);
        $collections = [];

        foreach ($this->collectionRegistry->forContext($context->context) as $collection) {
            $previewContext = $collection->getPreviewContext($context);
            $previewContext = new DataContext(
                $previewContext->context,
                $previewContext->itemId,
                $previewContext->item,
                $previewContext->fieldValues,
                new CollectionIteration(0, 1, 1, 1),
            );
            $collections[] = [
                'name' => $collection->getName(),
                'label' => $collection->getLabel(),
                'dataSourceCatalog' => $this->buildSourceCatalog($previewContext, $excludedSources),
            ];
        }

        usort(
            $collections,
            static fn (array $left, array $right): int => strnatcasecmp($left['label'], $right['label']),
        );

        return $catalog + ['collections' => $collections];
    }

    /** @return array{sources: list<array<string, mixed>>} */
    private function buildSourceCatalog(DataContext $context, array $excludedSources): array
    {
        $catalog = $this->catalogBuilder->build($context, $excludedSources);

        if ($context->iteration !== null) {
            $catalog['sources'][] = [
                'name' => 'iteration',
                'label' => Text::_('COM_MICROSCHEMA_DATA_SOURCE_ITERATION'),
                'fields' => [
                    ['name' => 'position', 'label' => Text::_('COM_MICROSCHEMA_DATA_SOURCE_ITERATION_POSITION'), 'type' => 'Integer', 'object' => false],
                    ['name' => 'index', 'label' => Text::_('COM_MICROSCHEMA_DATA_SOURCE_ITERATION_INDEX'), 'type' => 'Integer', 'object' => false],
                    ['name' => 'count', 'label' => Text::_('COM_MICROSCHEMA_DATA_SOURCE_ITERATION_COUNT'), 'type' => 'Integer', 'object' => false],
                    ['name' => 'total', 'label' => Text::_('COM_MICROSCHEMA_DATA_SOURCE_ITERATION_TOTAL'), 'type' => 'Integer', 'object' => false],
                ],
            ];
        }

        return $catalog;
    }

    private function resolveString(string $template, DataContext $context): mixed
    {
        if (preg_match(self::PLACEHOLDER_PATTERN, $template, $match) !== 1) {
            return $template;
        }

        if ($match[0] === $template) {
            $resolution = $this->resolvePlaceholder($match[1], $match[2], $context);

            return $resolution['valid'] ? $resolution['value'] : null;
        }

        $failed = false;
        $resolved = preg_replace_callback(
            self::PLACEHOLDER_PATTERN,
            function (array $match) use ($context, &$failed): string {
                $resolution = $this->resolvePlaceholder($match[1], $match[2], $context);
                $value = $resolution['value'];

                if (!$resolution['valid']) {
                    $failed = true;

                    return '';
                }

                if ($value === null || $value === '') {
                    return '';
                }

                if (!is_scalar($value) && !$value instanceof \Stringable) {
                    $failed = true;
                    $this->log(sprintf(
                        'MicroSchema: placeholder "%s" returned a non-scalar value and cannot be embedded into a text template in context "%s".',
                        $match[0],
                        $context->context,
                    ));

                    return '';
                }

                return (string) $value;
            },
            $template,
        );

        return $failed ? null : $resolved;
    }

    /** @return array{valid: bool, value: mixed} */
    private function resolvePlaceholder(string $sourceName, string $path, DataContext $context): array
    {
        $placeholder = sprintf('{%s.%s}', $sourceName, $path);

        if ($sourceName === 'iteration') {
            return $this->resolveIterationPlaceholder($path, $placeholder, $context);
        }

        if (!$this->sourceRegistry->has($sourceName, $context->context)) {
            return $this->invalid(sprintf(
                'MicroSchema: data source "%s" is not registered for context "%s" while resolving placeholder "%s".',
                $sourceName,
                $context->context,
                $placeholder,
            ));
        }

        $source = $this->sourceRegistry->get($sourceName, $context->context);

        $segments = explode('.', $path);
        $typeName = $source->getType();
        $value = $source->getValue($context);

        foreach ($segments as $index => $segment) {
            if (!$this->typeRegistry->has($typeName)) {
                return $this->invalid(sprintf(
                    'MicroSchema: data type "%s" is not registered while resolving placeholder "%s" in context "%s".',
                    $typeName,
                    $placeholder,
                    $context->context,
                ));
            }

            $type = $this->typeRegistry->get($typeName);
            $field = $this->findField($type->getFields($value, $context), $segment);

            if ($field === null) {
                return $this->invalid(sprintf(
                    'MicroSchema: field "%s" is not declared by data type "%s" for placeholder "%s" in context "%s".',
                    $segment,
                    $typeName,
                    $placeholder,
                    $context->context,
                ));
            }

            if ($value !== null) {
                $value = $type->resolve($value, $segment, $context);
            }

            $last = $index === array_key_last($segments);

            if ($last) {
                if ($this->typeRegistry->has($field->type)) {
                    return $this->invalid(sprintf(
                        'MicroSchema: object field "%s" of data type "%s" cannot be used as a terminal value in placeholder "%s".',
                        $segment,
                        $typeName,
                        $placeholder,
                    ));
                }

                if ($value === null || $value === '') {
                    $this->log(sprintf(
                        'MicroSchema: placeholder "%s" is valid but resolved to an empty value in context "%s".',
                        $placeholder,
                        $context->context,
                    ));
                }

                return ['valid' => true, 'value' => $value];
            }

            if (!$this->typeRegistry->has($field->type)) {
                return $this->invalid(sprintf(
                    'MicroSchema: scalar field "%s" of data type "%s" cannot contain nested segments in placeholder "%s".',
                    $segment,
                    $typeName,
                    $placeholder,
                ));
            }

            $typeName = $field->type;
        }

        return $this->invalid(sprintf('MicroSchema: placeholder "%s" is invalid.', $placeholder));
    }

    /** @return array{valid: bool, value: mixed} */
    private function resolveIterationPlaceholder(string $path, string $placeholder, DataContext $context): array
    {
        if ($context->iteration === null) {
            return $this->invalid(sprintf(
                'MicroSchema: iteration placeholder "%s" is unavailable outside a collection template in context "%s".',
                $placeholder,
                $context->context,
            ));
        }

        $value = match ($path) {
            'index' => $context->iteration->index,
            'position' => $context->iteration->position,
            'count' => $context->iteration->count,
            'total' => $context->iteration->total,
            default => null,
        };

        return $value === null
            ? $this->invalid(sprintf('MicroSchema: iteration field "%s" is not declared.', $path))
            : ['valid' => true, 'value' => $value];
    }

    private function isCollectionTemplate(mixed $value): bool
    {
        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        return is_array($value)
            && isset($value['collection'])
            && is_string($value['collection'])
            && array_key_exists('type', $value)
            && array_key_exists('data', $value);
    }

    /**
     * @param array<string, mixed>|object $template
     * @param list<string>                $collectionStack
     *
     * @return list<mixed>
     */
    private function resolveCollectionTemplate(
        array|object $template,
        DataContext $context,
        array $collectionStack,
        int $depth,
    ): array {
        $template = is_object($template) ? get_object_vars($template) : $template;
        $collectionName = trim((string) ($template['collection'] ?? ''));

        if ($depth >= self::COLLECTION_MAX_DEPTH || in_array($collectionName, $collectionStack, true)) {
            $this->log(sprintf(
                'MicroSchema: recursive or excessively nested data collection "%s" was rejected in context "%s".',
                $collectionName,
                $context->context,
            ));

            return [];
        }

        if (!$this->collectionRegistry->has($collectionName, $context->context)) {
            $this->log(sprintf(
                'MicroSchema: data collection "%s" is not registered for context "%s".',
                $collectionName,
                $context->context,
            ));

            return [];
        }

        $collection = $this->collectionRegistry->get($collectionName, $context->context);
        $result = $collection->getItems($context);
        $count = count($result->items);
        $total = $result->total ?? $count;
        $resolved = [];
        $stack = [...$collectionStack, $collectionName];
        $value = ['type' => $template['type'], 'data' => $template['data']];

        foreach ($result->items as $index => $itemContext) {
            $itemContext = new DataContext(
                $itemContext->context,
                $itemContext->itemId,
                $itemContext->item,
                $itemContext->fieldValues,
                new CollectionIteration($index, $result->offset + $index + 1, $count, $total),
            );
            $resolved[] = $this->resolveValue($value, $itemContext, $stack, $depth + 1);
        }

        return $resolved;
    }

    /** @param list<DataSourceField> $fields */
    private function findField(array $fields, string $name): ?DataSourceField
    {
        foreach ($fields as $field) {
            if ($field->name === $name) {
                return $field;
            }
        }

        return null;
    }

    /** @return array{valid: false, value: null} */
    private function invalid(string $message): array
    {
        $this->log($message);

        return ['valid' => false, 'value' => null];
    }

    private function log(string $message): void
    {
        if ($this->debugLogger !== null) {
            ($this->debugLogger)($message);
        }
    }
}
