<?php

/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Microschema\Administrator\Table;

defined('_JEXEC') || exit;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/**
 * Microschema item table.
 *
 * @since  0.1.1
 */
class ItemTable extends Table implements CurrentUserInterface
{
    use CurrentUserTrait;

    /**
     * Constructor.
     *
     * @param DatabaseInterface $db database connector object
     *
     * @since   0.1.1
     */
    public function __construct(DatabaseInterface $db)
    {
        parent::__construct('#__microschema_items', 'id', $db);
    }

    /**
     * Bind data to the table and serialize params as JSON.
     *
     * @param array|object $src    source data
     * @param array|string $ignore fields to ignore
     *
     * @return bool
     *
     * @since   0.1.1
     */
    public function bind($src, $ignore = '')
    {
        $data = (array) $src;

        if (array_key_exists('params', $data)) {
            try {
                if ($data['params'] instanceof Registry) {
                    $data['params'] = $data['params']->toString('JSON');
                } elseif (is_array($data['params']) || is_object($data['params'])) {
                    $data['params'] = json_encode($data['params'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                }
            } catch (\JsonException $exception) {
                $this->setError($exception->getMessage());

                return false;
            }
        }

        return parent::bind($data, $ignore);
    }

    /**
     * Validate the record before it is stored.
     *
     * @return bool
     *
     * @since   0.1.1
     */
    public function check()
    {
        $this->context = trim((string) $this->context);
        $this->item_id = (int) $this->item_id;

        if ($this->context === '') {
            throw new \UnexpectedValueException('The microschema context must not be empty.');
        }

        if (strlen($this->context) > 100) {
            throw new \UnexpectedValueException('The microschema context must not exceed 100 characters.');
        }

        if ($this->item_id < 1) {
            throw new \UnexpectedValueException('The microschema item ID must be a positive integer.');
        }

        try {
            json_decode((string) $this->params, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \UnexpectedValueException('The microschema params must contain valid JSON.', 0, $exception);
        }

        return parent::check();
    }

    /**
     * Store the record and maintain its audit fields.
     *
     * @param bool $updateNulls store null values
     *
     * @return bool
     *
     * @since   0.1.1
     */
    public function store($updateNulls = true)
    {
        $date = Date::getInstance()->toSql(false, $this->getDatabase());
        $userId = (int) $this->getCurrentUser()->id;

        if (empty($this->id)) {
            $this->created = $this->created ?: $date;
            $this->created_by = $this->created_by ?: $userId;
        } else {
            $this->modified = $date;
            $this->modified_by = $userId;
        }

        return parent::store($updateNulls);
    }
}
