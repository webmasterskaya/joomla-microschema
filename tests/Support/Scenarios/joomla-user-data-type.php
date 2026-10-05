<?php

declare(strict_types=1);

namespace Joomla\CMS\Language {
    class Text
    {
        public static function _(string $key): string
        {
            return $key;
        }
    }
}

namespace Joomla\CMS\User {
    class User
    {
        public function __construct(
            public int $id,
            public string $name,
            public string $username,
            public string $email,
            public string $registerDate = '',
            public string $lastvisitDate = '',
        ) {
        }
    }

    interface UserFactoryInterface
    {
        public function loadUserById(int $id): User;
    }
}

namespace {
    use Joomla\CMS\User\User;
    use Joomla\CMS\User\UserFactoryInterface;
    use Joomla\Component\Microschema\Administrator\DataSource\DataContext;
    use Joomla\Component\Microschema\Administrator\DataSource\ContextualDataValue;
    use Joomla\Plugin\System\Microschema\DataType\JoomlaUserDataType;

    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataContext.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/ContextualDataValue.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataSourceField.php';
    require_once __DIR__ . '/../../../com_microschema/administrator/src/DataSource/DataTypeInterface.php';
    require_once __DIR__ . '/../../../plugins/system/microschema/src/DataType/JoomlaUserDataType.php';

    function assertUserDataTypeSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message);
        }
    }

    $factory = new class () implements UserFactoryInterface {
        public function loadUserById(int $id): User
        {
            return new User($id, 'Registered name', 'ada', 'ada@example.test');
        }
    };
    $type    = new JoomlaUserDataType($factory);
    $context = new DataContext('com_content.article', 42, []);

    assertUserDataTypeSame('JoomlaUser', $type->getName(), 'The Joomla user type name must be reusable.');
    assertUserDataTypeSame('Registered name', $type->resolve(9, 'name', $context), 'A user ID must be loaded by the user factory.');
    assertUserDataTypeSame('ada', $type->resolve(['id' => 9], 'username', $context), 'A reference array must fall back to the loaded user.');
    assertUserDataTypeSame('Guest author', $type->resolve(['id' => 9, 'name' => 'Guest author'], 'name', $context), 'A relationship may override a user field.');
    assertUserDataTypeSame(null, $type->resolve(null, 'name', $context), 'A missing user reference must resolve to null.');
    $contextualUser = new ContextualDataValue('com_users.user', 9, 9, overrides: ['name' => 'Guest author']);
    assertUserDataTypeSame('Guest author', $type->resolve($contextualUser, 'name', $context), 'Contextual relationships may override user fields.');
    assertUserDataTypeSame($contextualUser, $type->resolve($contextualUser, 'fields', $context), 'User fields must retain their owner context.');
    assertUserDataTypeSame(
        'JoomlaCustomFields',
        $type->getFields($contextualUser, $context)[6]->type ?? null,
        'The user type must expose contextual custom fields.',
    );

    echo "Joomla user data type tests passed.\n";
}
