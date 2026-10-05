<?php

declare(strict_types=1);

namespace Joomla\Database {
    interface DatabaseInterface
    {
    }
}

namespace Joomla\CMS\Date {
    use Joomla\Database\DatabaseInterface;

    class Date
    {
        public static ?DatabaseInterface $database = null;

        public static function getInstance(): static
        {
            return new static();
        }

        public function toSql(bool $local = false, ?DatabaseInterface $db = null): string
        {
            self::$database = $db;

            return '2026-08-20 14:30:00';
        }
    }
}

namespace Joomla\Registry {
    class Registry
    {
        public function __construct(private readonly array $data = [])
        {
        }

        public function toString(string $format): string
        {
            return json_encode($this->data, JSON_THROW_ON_ERROR);
        }
    }
}

namespace Joomla\CMS\User {
    class User
    {
        public function __construct(public int $id = 0)
        {
        }
    }

    interface CurrentUserInterface
    {
        public function setCurrentUser(User $currentUser): void;
    }

    trait CurrentUserTrait
    {
        private ?User $currentUser = null;

        public function setCurrentUser(User $currentUser): void
        {
            $this->currentUser = $currentUser;
        }

        protected function getCurrentUser(): User
        {
            return $this->currentUser ?? new User();
        }
    }
}

namespace Joomla\CMS\Table {
    use Joomla\Database\DatabaseInterface;

    #[\AllowDynamicProperties]
    class Table
    {
        public int $id = 0;

        public ?string $created = null;

        public int $created_by = 0;

        public ?string $modified = null;

        public int $modified_by = 0;

        public bool $stored = false;

        public function __construct(
            string $table,
            string $key,
            private readonly DatabaseInterface $database,
        ) {
        }

        public function bind($src, $ignore = '')
        {
            foreach ((array) $src as $key => $value) {
                $this->{$key} = $value;
            }

            return true;
        }

        public function check()
        {
            return true;
        }

        public function store($updateNulls = true)
        {
            $this->stored = true;

            return true;
        }

        public function getDatabase(): DatabaseInterface
        {
            return $this->database;
        }

        public function setError(string $error): void
        {
        }
    }
}

namespace {
    use Joomla\CMS\Date\Date;
    use Joomla\CMS\User\User;
    use Joomla\Component\Microschema\Administrator\Table\ItemTable;
    use Joomla\Database\DatabaseInterface;

    define('_JEXEC', 1);

    require_once __DIR__ . '/../../../com_microschema/administrator/src/Table/ItemTable.php';

    function assertItemTableSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message);
        }
    }

    $database = new class () implements DatabaseInterface {
    };
    $table = new ItemTable($database);
    $table->setCurrentUser(new User(99));

    assertItemTableSame(true, $table->bind([
        'context' => 'com_content.article',
        'item_id' => 42,
        'params'  => ['schema_type' => 'Article'],
    ]), 'Item data must bind.');
    assertItemTableSame(
        '{"schema_type":"Article"}',
        $table->params,
        'Array params must be serialized as JSON.',
    );
    assertItemTableSame(true, $table->check(), 'Valid item data must pass validation.');
    assertItemTableSame(true, $table->store(), 'A new item must be stored.');
    assertItemTableSame('2026-08-20 14:30:00', $table->created, 'A new item must receive its creation date.');
    assertItemTableSame(99, $table->created_by, 'A new item must receive its creator ID.');
    assertItemTableSame($database, Date::$database, 'Date formatting must use the injected database.');

    $table->id = 7;

    assertItemTableSame(true, $table->store(), 'An existing item must be stored.');
    assertItemTableSame('2026-08-20 14:30:00', $table->modified, 'An existing item must receive its modification date.');
    assertItemTableSame(99, $table->modified_by, 'An existing item must receive its modifier ID.');

    echo "Item table tests passed.\n";
}
