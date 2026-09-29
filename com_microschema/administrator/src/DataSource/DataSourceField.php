<?php

namespace Joomla\Component\Microschema\Administrator\DataSource;

final readonly class DataSourceField
{
    public function __construct(
        public string $name,
        public string $label,
        public string $type = 'String',
    ) {
        if ($name === '' || preg_match('/^[A-Za-z0-9_-]+$/', $name) !== 1) {
            throw new \InvalidArgumentException('A data source field name must be a non-empty path segment.');
        }

        if ($label === '') {
            throw new \InvalidArgumentException('A data source field label must not be empty.');
        }

        if ($type === '' || preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $type) !== 1) {
            throw new \InvalidArgumentException('A data source field type must be a non-empty type name.');
        }
    }
}
