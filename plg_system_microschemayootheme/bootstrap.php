<?php

/**
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') || exit;

use YOOtheme\Builder;
use YOOtheme\Path;

return [
    'extend' => [
        Builder::class => static function (Builder $builder): void {
            $builder->addTypePath(Path::get(__DIR__.'/elements/*/element.json'));
        },
    ],
];
