<?php

namespace Joomla\Component\Microschema\Administrator\DataSource;

final class DataTypeRegistry
{
    /** @var array<string, DataTypeInterface> */
    private array $types = [];

    public function register(DataTypeInterface $type): void
    {
        $name = trim($type->getName());

        if ($name === '') {
            throw new \InvalidArgumentException('Data type name must not be empty.');
        }

        if (isset($this->types[$name]) && $this->types[$name] !== $type) {
            throw new \LogicException(sprintf('Data type "%s" is already registered.', $name));
        }

        $this->types[$name] = $type;
    }

    public function has(string $name): bool
    {
        return isset($this->types[$name]);
    }

    public function get(string $name): DataTypeInterface
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(sprintf('Data type "%s" is not registered.', $name));
        }

        return $this->types[$name];
    }

    /** @return array<string, DataTypeInterface> */
    public function all(): array
    {
        return $this->types;
    }
}
