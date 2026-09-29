<?php

namespace Joomla\Component\Microschema\Administrator\DataSource;

final class DataSourceRegistry
{
    /** @var array<string, list<DataSourceInterface>> */
    private array $sources = [];

    public function register(DataSourceInterface $source): void
    {
        $name = trim($source->getName());

        if ($name === '') {
            throw new \InvalidArgumentException('Data source name must not be empty.');
        }

        foreach ($this->sources[$name] ?? [] as $registered) {
            if ($registered === $source) {
                return;
            }
        }

        $this->sources[$name][] = $source;
    }

    public function has(string $name, ?string $context = null): bool
    {
        if ($context === null) {
            return isset($this->sources[$name]);
        }

        return count($this->matching($name, $context)) === 1;
    }

    public function get(string $name, ?string $context = null): DataSourceInterface
    {
        $sources = $context === null ? ($this->sources[$name] ?? []) : $this->matching($name, $context);

        if ($sources === []) {
            throw new \InvalidArgumentException(sprintf('Data source "%s" is not registered.', $name));
        }

        if (count($sources) !== 1) {
            throw new \LogicException(sprintf('Data source "%s" is ambiguous for the requested context.', $name));
        }

        return $sources[0];
    }

    /** @return list<DataSourceInterface> */
    public function all(): array
    {
        return array_merge(...array_values($this->sources));
    }

    /** @return array<string, DataSourceInterface> */
    public function forContext(string $context): array
    {
        $sources = [];

        foreach (array_keys($this->sources) as $name) {
            $matching = $this->matching($name, $context);

            if (count($matching) > 1) {
                throw new \LogicException(sprintf('Data source "%s" is ambiguous for context "%s".', $name, $context));
            }

            if ($matching !== []) {
                $sources[$name] = $matching[0];
            }
        }

        return $sources;
    }

    /** @return list<DataSourceInterface> */
    private function matching(string $name, string $context): array
    {
        return array_values(array_filter(
            $this->sources[$name] ?? [],
            static fn (DataSourceInterface $source): bool => $source->supportsContext($context),
        ));
    }
}
