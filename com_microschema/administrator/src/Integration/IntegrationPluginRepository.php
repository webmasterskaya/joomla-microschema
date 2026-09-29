<?php

namespace Joomla\Component\Microschema\Administrator\Integration;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final readonly class IntegrationPluginRepository
{
    public function __construct(private DatabaseInterface $database)
    {
    }

    /**
     * @return list<array{id: int, name: string, element: string, enabled: bool}>
     */
    public function getAll(): array
    {
        $type = 'plugin';
        $folder = 'microschema';
        $query = $this->database->createQuery()
            ->select($this->database->quoteName(['extension_id', 'name', 'element', 'enabled']))
            ->from($this->database->quoteName('#__extensions'))
            ->where($this->database->quoteName('type').' = :type')
            ->where($this->database->quoteName('folder').' = :folder')
            ->order($this->database->quoteName('ordering').' ASC')
            ->order($this->database->quoteName('name').' ASC')
            ->bind(':type', $type, ParameterType::STRING)
            ->bind(':folder', $folder, ParameterType::STRING);
        $rows = $this->database->setQuery($query)->loadAssocList();

        if (!is_array($rows)) {
            return [];
        }

        return array_values(array_map(
            static fn (array $row): array => [
                'id' => (int) ($row['extension_id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'element' => (string) ($row['element'] ?? ''),
                'enabled' => (bool) ($row['enabled'] ?? false),
            ],
            $rows,
        ));
    }
}
