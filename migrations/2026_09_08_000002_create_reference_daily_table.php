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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return Migration::createTable('reference_daily', function (Blueprint $table, Builder $schema) {
    $table->date('date');
    $table->unsignedInteger('target_discussion_id');
    $table->unsignedInteger('total')->default(0);

    $table->primary(['date', 'target_discussion_id']);
    // Prefixed for the same reason as the references table: Postgres scopes an
    // index name to the schema, not to its table.
    $table->index('date', $schema->getConnection()->getTablePrefix().'ref_daily_date_idx');
});
