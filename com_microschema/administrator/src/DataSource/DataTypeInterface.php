<?php

namespace Joomla\Component\Microschema\Administrator\DataSource;

interface DataTypeInterface
{
    public function getName(): string;

    /** @return list<DataSourceField> */
    public function getFields(mixed $value, DataContext $context): array;

    public function resolve(mixed $value, string $field, DataContext $context): mixed;
}
