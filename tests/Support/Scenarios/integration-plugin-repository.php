<?php

declare(strict_types=1);

namespace Joomla\Database {
    interface DatabaseInterface
    {
        public function createQuery(): object;

        public function quoteName(string|array $name): string|array;

        public function setQuery(object $query): static;

        public function loadAssocList(): array;
    }

    final class ParameterType
    {
        public const STRING = 'string';
    }
}

namespace {
    use Joomla\Component\Microschema\Administrator\Integration\IntegrationPluginRepository;
    use Joomla\Database\DatabaseInterface;

    require_once __DIR__ . '/../../../com_microschema/administrator/src/Integration/IntegrationPluginRepository.php';

    function assertIntegrationRepositorySame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message);
        }
    }

    $query = new class () {
        public array $select = [];
        public string $from = '';
        public array $where = [];
        public array $order = [];
        public array $bindings = [];

        public function select(array|string $columns): static
        {
            $this->select = (array) $columns;

            return $this;
        }

        public function from(string $table): static
        {
            $this->from = $table;

            return $this;
        }

        public function where(string $condition): static
        {
            $this->where[] = $condition;

            return $this;
        }

        public function order(string $ordering): static
        {
            $this->order[] = $ordering;

            return $this;
        }

        public function bind(string $key, mixed &$value, string $type): static
        {
            $this->bindings[$key] = compact('value', 'type');

            return $this;
        }
    };
    $database = new class ($query) implements DatabaseInterface {
        public ?object $executedQuery = null;

        public function __construct(private readonly object $query)
        {
        }

        public function createQuery(): object
        {
            return $this->query;
        }

        public function quoteName(string|array $name): string|array
        {
            if (is_array($name)) {
                return array_map(fn (string $item): string => (string) $this->quoteName($item), $name);
            }

            return '`' . $name . '`';
        }

        public function setQuery(object $query): static
        {
            $this->executedQuery = $query;

            return $this;
        }

        public function loadAssocList(): array
        {
            return [
                [
                    'extension_id' => '12',
                    'name'         => 'plg_microschema_content',
                    'element'      => 'content',
                    'enabled'      => '1',
                ],
                [
                    'extension_id' => '14',
                    'name'         => 'plg_microschema_contact',
                    'element'      => 'contact',
                    'enabled'      => '0',
                ],
            ];
        }
    };

    $integrations = (new IntegrationPluginRepository($database))->getAll();

    assertIntegrationRepositorySame(
        [
            ['id' => 12, 'name' => 'plg_microschema_content', 'element' => 'content', 'enabled' => true],
            ['id' => 14, 'name' => 'plg_microschema_contact', 'element' => 'contact', 'enabled' => false],
        ],
        $integrations,
        'The repository must normalize enabled and disabled integration plugins.',
    );
    assertIntegrationRepositorySame('plugin', $query->bindings[':type']['value'] ?? null, 'The repository must restrict the extension type.');
    assertIntegrationRepositorySame('microschema', $query->bindings[':folder']['value'] ?? null, 'The repository must restrict the integration plugin group.');
    assertIntegrationRepositorySame('`#__extensions`', $query->from, 'The repository must query Joomla extensions.');
    assertIntegrationRepositorySame(['`ordering` ASC', '`name` ASC'], $query->order, 'The integration list order must be deterministic.');

    echo "Integration plugin repository tests passed.\n";
}
