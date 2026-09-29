<?php

/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Microschema\Administrator\Model;

defined('_JEXEC') || exit;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;

/**
 * Microschema item model.
 *
 * @since  0.1.1
 */
class ItemModel extends BaseDatabaseModel
{
    /**
     * Return the item table.
     *
     * @param string $name    table name
     * @param string $prefix  table class prefix
     * @param array  $options table options
     *
     * @since   0.1.1
     */
    public function getTable($name = 'Item', $prefix = 'Administrator', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Load an item by its component context and source item ID.
     *
     * @param string $context component context
     * @param int    $itemId  source item ID
     *
     * @since   0.1.1
     */
    public function getItemByContext(string $context, int $itemId): ?Table
    {
        $table = $this->getTable();

        if (!$table->load(['context' => $context, 'item_id' => $itemId])) {
            return null;
        }

        return $table;
    }

    /**
     * Load decoded item settings by their component context and source item ID.
     *
     * @param string $context component context
     * @param int    $itemId  source item ID
     *
     * @return array<string, mixed>
     *
     * @since   0.1.1
     */
    public function getParamsByContext(string $context, int $itemId): array
    {
        $item = $this->getItemByContext($context, $itemId);

        if ($item === null) {
            return [];
        }

        try {
            $params = json_decode((string) $item->params, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \UnexpectedValueException('The stored microschema params contain invalid JSON.', 0, $exception);
        }

        if (!is_array($params)) {
            throw new \UnexpectedValueException('The stored microschema params must decode to an array.');
        }

        return $params;
    }

    /**
     * Insert or update item settings by their component context and source item ID.
     *
     * @param string               $context component context
     * @param int                  $itemId  source item ID
     * @param array<string, mixed> $params  item settings
     *
     * @since   0.1.1
     */
    public function saveParamsByContext(string $context, int $itemId, array $params): bool
    {
        $table = $this->getItemByContext($context, $itemId) ?? $this->getTable();

        if (!$table->bind([
            'context' => $context,
            'item_id' => $itemId,
            'params' => $params,
            'state' => 1,
        ])) {
            return false;
        }

        return $table->check() && $table->store();
    }

    /**
     * Delete item settings by their component context and source item ID.
     *
     * @param string $context component context
     * @param int    $itemId  source item ID
     *
     * @since   0.1.1
     */
    public function deleteItemByContext(string $context, int $itemId): bool
    {
        $table = $this->getItemByContext($context, $itemId);

        return $table === null || $table->delete();
    }
}
