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

return Migration::createTable('reference_daily', function (Blueprint $table) {
    $table->date('date');
    $table->unsignedInteger('target_discussion_id');
    $table->unsignedInteger('total')->default(0);

    $table->primary(['date', 'target_discussion_id']);
    $table->index('date', 'reference_daily_date_idx');
});
