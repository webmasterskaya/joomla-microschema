<?php

namespace Joomla\Component\Microschema\Administrator\DataCollection;

final class DataCollectionRegistry
{
    /** @var array<string, list<DataCollectionInterface>> */
    private array $collections = [];

    public function register(DataCollectionInterface $collection): void
    {
        $name = trim($collection->getName());

        if ($name === '') {
            throw new \InvalidArgumentException('Data collection name must not be empty.');
        }

        foreach ($this->collections[$name] ?? [] as $registered) {
            if ($registered === $collection) {
                return;
            }
        }

        $this->collections[$name][] = $collection;
    }

    public function has(string $name, ?string $context = null): bool
    {
        if ($context === null) {
            return isset($this->collections[$name]);
        }

        return count($this->matching($name, $context)) === 1;
    }

    public function get(string $name, ?string $context = null): DataCollectionInterface
    {
        $collections = $context === null
            ? ($this->collections[$name] ?? [])
            : $this->matching($name, $context);

        if ($collections === []) {
            throw new \InvalidArgumentException(sprintf('Data collection "%s" is not registered.', $name));
        }

        if (count($collections) !== 1) {
            throw new \LogicException(sprintf('Data collection "%s" is ambiguous for the requested context.', $name));
        }

        return $collections[0];
    }

    /** @return array<string, DataCollectionInterface> */
    public function forContext(string $context): array
    {
        $collections = [];

        foreach (array_keys($this->collections) as $name) {
            $matching = $this->matching($name, $context);

            if (count($matching) > 1) {
                throw new \LogicException(sprintf('Data collection "%s" is ambiguous for context "%s".', $name, $context));
            }

            if ($matching !== []) {
                $collections[$name] = $matching[0];
            }
        }

        return $collections;
    }

    /** @return list<DataCollectionInterface> */
    private function matching(string $name, string $context): array
    {
        return array_values(array_filter(
            $this->collections[$name] ?? [],
            static fn (DataCollectionInterface $collection): bool => $collection->supportsContext($context),
        ));
    }
}
