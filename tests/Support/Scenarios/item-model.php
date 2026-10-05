<?php

declare(strict_types=1);

namespace Joomla\CMS\Table {
    class Table
    {
        public mixed $params = '{}';

        /** @var array<string, mixed> */
        public array $bound = [];

        public bool $loadResult = false;

        public bool $stored = false;

        public bool $deleted = false;

        public function load(mixed $keys): bool
        {
            return $this->loadResult;
        }

        public function bind(mixed $data): bool
        {
            $this->bound = (array) $data;

            return true;
        }

        public function check(): bool
        {
            return true;
        }

        public function store(): bool
        {
            $this->stored = true;

            return true;
        }

        public function delete(mixed $pk = null): bool
        {
            $this->deleted = true;

            return true;
        }
    }
}

namespace Joomla\CMS\MVC\Model {
    use Joomla\CMS\Table\Table;

    abstract class BaseDatabaseModel
    {
        /** @var list<Table> */
        public static array $tables = [];

        public function getTable($name = '', $prefix = '', $options = []): Table
        {
            $table = array_shift(self::$tables);

            if (!$table instanceof Table) {
                throw new \RuntimeException('No table stub was queued.');
            }

            return $table;
        }
    }
}

namespace {
    use Joomla\CMS\MVC\Model\BaseDatabaseModel;
    use Joomla\CMS\Table\Table;
    use Joomla\Component\Microschema\Administrator\Model\ItemModel;

    define('_JEXEC', 1);

    require_once __DIR__ . '/../../../com_microschema/administrator/src/Model/ItemModel.php';

    function assertItemModelSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message);
        }
    }

    $model                     = new ItemModel();
    $storedTable               = new Table();
    $storedTable->loadResult   = true;
    $storedTable->params       = '{"schema_type":"Article","schema_properties":{"headline":"Example"}}';
    BaseDatabaseModel::$tables = [$storedTable];

    assertItemModelSame(
        ['schema_type' => 'Article', 'schema_properties' => ['headline' => 'Example']],
        $model->getParamsByContext('com_content.article', 42),
        'Stored params must be decoded.',
    );

    $missingTable              = new Table();
    $insertTable               = new Table();
    BaseDatabaseModel::$tables = [$missingTable, $insertTable];
    $settings                  = ['schema_type' => 'Article', 'schema_properties' => []];
    $saveResult                = $model->saveParamsByContext('com_content.article', 42, $settings);

    assertItemModelSame(true, $saveResult, 'New settings must be saved.');
    assertItemModelSame(
        [
            'context' => 'com_content.article',
            'item_id' => 42,
            'params'  => $settings,
            'state'   => 1,
        ],
        $insertTable->bound,
        'New settings must use the context and source item ID.',
    );
    assertItemModelSame(true, $insertTable->stored, 'The inserted table must be stored.');

    $deleteTable               = new Table();
    $deleteTable->loadResult   = true;
    BaseDatabaseModel::$tables = [$deleteTable];

    assertItemModelSame(true, $model->deleteItemByContext('com_content.article', 42), 'Existing settings must be deleted.');
    assertItemModelSame(true, $deleteTable->deleted, 'The loaded table must receive delete().');

    $absentTable              = new Table();
    BaseDatabaseModel::$tables = [$absentTable];
    assertItemModelSame(true, $model->deleteItemByContext('com_content.article', 404), 'Deleting absent settings must succeed.');

    echo "Item model tests passed.\n";
}
