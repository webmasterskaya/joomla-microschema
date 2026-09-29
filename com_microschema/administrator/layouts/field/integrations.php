<?php

defined('_JEXEC') || exit;

use Joomla\CMS\Language\Text;

/** @var array{integrations: list<array{id: int, label: string, enabled: bool, url: string}>, canConfigure: bool} $displayData */
$integrations = $displayData['integrations'];

if ($integrations === []) {
    ?>
    <div class="alert alert-info mb-0">
        <?php echo Text::_('COM_MICROSCHEMA_INTEGRATIONS_EMPTY'); ?>
    </div>
    <?php
    return;
}
?>
<div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th scope="col"><?php echo Text::_('COM_MICROSCHEMA_INTEGRATIONS_NAME'); ?></th>
                <th scope="col"><?php echo Text::_('JSTATUS'); ?></th>
                <th scope="col" class="text-end"><?php echo Text::_('COM_MICROSCHEMA_INTEGRATIONS_ACTIONS'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($integrations as $integration) { ?>
                <tr>
                    <th scope="row">
                        <?php echo htmlspecialchars($integration['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </th>
                    <td>
                        <span class="badge <?php echo $integration['enabled'] ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo Text::_($integration['enabled'] ? 'JENABLED' : 'JDISABLED'); ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <?php if ($displayData['canConfigure']) { ?>
                            <a
                                class="btn btn-sm btn-primary"
                                href="<?php echo htmlspecialchars($integration['url'], ENT_QUOTES, 'UTF-8'); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <?php echo Text::_('COM_MICROSCHEMA_INTEGRATIONS_CONFIGURE'); ?>
                            </a>
                        <?php } else { ?>
                            <span class="text-muted">&mdash;</span>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
