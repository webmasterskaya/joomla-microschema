<?php

declare(strict_types=1);

function assertComponentConfigSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . PHP_EOL
            . 'Expected: ' . var_export($expected, true) . PHP_EOL
            . 'Actual: ' . var_export($actual, true),
        );
    }
}

$config = simplexml_load_file(__DIR__ . '/../com_microschema/administrator/config.xml');

if (!$config instanceof SimpleXMLElement) {
    throw new RuntimeException('Unable to load the component configuration.');
}

foreach (['organization' => 'Organization', 'website' => 'WebSite'] as $fieldsetName => $schemaType) {
    $fields = $config->xpath(sprintf('/config/fieldset[@name="%s"]/field', $fieldsetName));

    if ($fields === false) {
        throw new RuntimeException(sprintf('Unable to read the "%s" fieldset.', $fieldsetName));
    }

    $properties = null;

    foreach ($fields as $field) {
        if ((string) $field['name'] === $fieldsetName . '_properties') {
            $properties = $field;
            break;
        }
    }

    if (!$properties instanceof SimpleXMLElement) {
        throw new RuntimeException(sprintf('The "%s" properties field is missing.', $fieldsetName));
    }

    assertComponentConfigSame('schemaOrgProperties', (string) $properties['type'], 'The schema editor field type is invalid.');
    assertComponentConfigSame($schemaType, (string) $properties['schematype'], 'The fixed Schema.org type is invalid.');
    assertComponentConfigSame(
        $fieldsetName . '_enabled:1',
        (string) $properties['showon'],
        'The schema editor visibility condition is invalid.',
    );
}

assertComponentConfigSame(
    0,
    count($config->xpath('/config/fieldset[@name="breadcrumblist"]') ?: []),
    'BreadcrumbList must not have a separate configuration tab.',
);
$breadcrumbFields = $config->xpath(
    '/config/fieldset[@name="settings"]/fieldset[@name="schemaorg_settings"]/field[@name="breadcrumblist_enabled"]',
);
assertComponentConfigSame(
    1,
    count($breadcrumbFields ?: []),
    'The BreadcrumbList switch must be placed in the Schema.org settings fieldset.',
);
assertComponentConfigSame(
    'schemaorg_enabled:1',
    (string) $breadcrumbFields[0]['showon'],
    'The BreadcrumbList switch visibility condition is invalid.',
);

$integrationFields = $config->xpath('/config/fieldset[@name="integrations"]/field[@name="integrations"]');
assertComponentConfigSame(1, count($integrationFields ?: []), 'The component configuration must contain an integrations tab.');
assertComponentConfigSame('integrations', (string) $integrationFields[0]['type'], 'The integrations tab must use its dedicated field.');

$provider = file_get_contents(__DIR__ . '/../com_microschema/administrator/services/provider.php');

assertComponentConfigSame(
    true,
    str_contains($provider, "defined('JDEBUG') && JDEBUG"),
    'The data source debug logger must only be enabled in Joomla debug mode.',
);
assertComponentConfigSame(
    true,
    str_contains($provider, "Log::add(\$message, Log::DEBUG, 'com_microschema')"),
    'The data source resolver must write to the MicroSchema Joomla debug category.',
);
assertComponentConfigSame(
    true,
    str_contains($provider, 'IntegrationPluginRepository::class'),
    'The integration repository must be registered in the component container.',
);

$integrationLayout = file_get_contents(__DIR__ . '/../com_microschema/administrator/layouts/field/integrations.php');
assertComponentConfigSame(true, str_contains($integrationLayout, 'target="_blank"'), 'Integration settings must open in a new browser tab.');
assertComponentConfigSame(true, str_contains($integrationLayout, 'noopener noreferrer'), 'The external-tab link must isolate its opener.');

$integrationField = file_get_contents(__DIR__ . '/../com_microschema/administrator/src/Field/IntegrationsField.php');
assertComponentConfigSame(true, str_contains($integrationField, 'task=plugin.edit&extension_id='), 'The configure action must use Joomla plugin editing.');
assertComponentConfigSame(
    true,
    str_contains($integrationField, "bootComponent('com_microschema')"),
    'The integrations field must resolve services through the booted component.',
);
assertComponentConfigSame(
    false,
    str_contains($integrationField, 'Factory::getContainer()'),
    'The integrations field must not request component services from the global container.',
);

echo "Component configuration tests passed.\n";
