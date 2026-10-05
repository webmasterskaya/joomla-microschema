<?php

declare(strict_types=1);

function assertComponentDashboard(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$controllerPath = __DIR__ . '/../com_microschema/administrator/src/Controller/DisplayController.php';
$viewPath       = __DIR__ . '/../com_microschema/administrator/src/View/Microschema/HtmlView.php';
$templatePath   = __DIR__ . '/../com_microschema/administrator/tmpl/microschema/default.php';

assertComponentDashboard(is_file($controllerPath), 'The administrator display controller is missing.');
assertComponentDashboard(is_file($viewPath), 'The administrator dashboard view is missing.');
assertComponentDashboard(is_file($templatePath), 'The administrator dashboard template is missing.');

$controller = file_get_contents($controllerPath);
$view       = file_get_contents($viewPath);
$template   = file_get_contents($templatePath);

assertComponentDashboard(str_contains($controller, 'extends BaseController'), 'The display controller must use Joomla BaseController.');
assertComponentDashboard(str_contains($controller, "protected \$default_view = 'microschema';"), 'The display controller must select the dashboard by default.');
assertComponentDashboard(str_contains($view, 'extends BaseHtmlView'), 'The dashboard must use Joomla HtmlView.');
assertComponentDashboard(str_contains($view, "Text::_('COM_MICROSCHEMA_DASHBOARD_TITLE')"), 'The dashboard must use its localized page title.');
assertComponentDashboard(
    str_contains($template, "LayoutHelper::render('joomla.content.emptystate', \$displayData)"),
    'The dashboard must use the Joomla empty-state layout.',
);
assertComponentDashboard(
    str_contains($template, "'textPrefix' => 'COM_MICROSCHEMA_DASHBOARD'"),
    'The empty-state layout must use the dashboard language prefix.',
);
assertComponentDashboard(
    str_contains($template, "'createURL' => 'index.php?option=com_config&view=component&component=com_microschema'"),
    'The settings button must open the component options using an unescaped route.',
);

foreach (['en-GB', 'ru-RU'] as $language) {
    $languageFile = file_get_contents(
        __DIR__ . '/../com_microschema/administrator/language/' . $language . '/com_microschema.ini',
    );

    assertComponentDashboard(
        str_contains($languageFile, 'COM_MICROSCHEMA_DASHBOARD_EMPTYSTATE_TITLE='),
        sprintf('The empty-state title is missing from the %s language file.', $language),
    );
    assertComponentDashboard(
        str_contains($languageFile, 'COM_MICROSCHEMA_DASHBOARD_EMPTYSTATE_CONTENT='),
        sprintf('The empty-state content is missing from the %s language file.', $language),
    );
    assertComponentDashboard(
        str_contains($languageFile, 'COM_MICROSCHEMA_DASHBOARD_EMPTYSTATE_BUTTON_ADD='),
        sprintf('The settings button label is missing from the %s language file.', $language),
    );
}

echo "Component dashboard tests passed.\n";
