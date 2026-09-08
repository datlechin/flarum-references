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

return Migration::createTable('post_references', function (Blueprint $table) {
    $table->increments('id');

    $table->unsignedInteger('source_discussion_id');
    $table->unsignedInteger('source_post_id')->nullable();

    $table->string('target_type', 100);
    $table->unsignedInteger('target_id');
    $table->unsignedInteger('target_discussion_id')->nullable();

    $table->string('relation_type', 20)->default('references');
    $table->string('origin', 10);

    $table->unsignedInteger('created_by_id')->nullable();
    $table->text('note')->nullable();

    $table->timestamp('target_deleted_at')->nullable();
    $table->timestamp('created_at')->useCurrent();
    $table->timestamp('updated_at')->nullable();

    $table->foreign('source_discussion_id')->references('id')->on('discussions')->onDelete('cascade');
    $table->foreign('source_post_id')->references('id')->on('posts')->onDelete('cascade');
    $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');

    // No key on the target: a cascade would delete the rows the broken link
    // report exists to show, and a polymorphic pair has no table to point at.

    // `origin` is left out on purpose. A post that both mentions and links the
    // same target is one assertion, and a second row would make
    // `referencedByCount` read 2 for a single citing post.
    // Unnamed on purpose. Laravel applies the connection's table prefix only
    // when it generates the name itself, and Postgres scopes an index to the
    // schema rather than to its table, so a hardcoded name collides between two
    // installs sharing one schema.
    $table->unique(['source_post_id', 'target_type', 'target_id']);

    $table->index(['target_type', 'target_id', 'id']);
    $table->index(['target_discussion_id', 'id']);
    $table->index('source_discussion_id');
    $table->index('target_deleted_at');
});
