<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Flarum\Database\Migration;
use Flarum\Group\Group;

return Migration::addPermissions([
    'datlechin-references.manageReferences' => Group::MODERATOR_ID,
    'datlechin-references.viewBrokenReport' => Group::MODERATOR_ID,
    'datlechin-references.viewAnalytics' => Group::ADMINISTRATOR_ID,
]);
