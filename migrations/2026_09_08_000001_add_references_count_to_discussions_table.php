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

// A forum wide ranking number, not a per reader count. What a reader is shown
// is computed live, because visibility is scoped to the actor and one stored
// integer cannot be right for a guest and a moderator at once.
return Migration::addColumns('discussions', [
    'references_count' => ['integer', 'unsigned' => true, 'default' => 0],
]);
