<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Builder;

/*
 * 1.0.0 recorded a post pointing back into its own discussion, which is what
 * every reply is: a post mention of the post above it. Those rows are dropped,
 * whatever a moderator said about them, and the ranking counter is rebuilt
 * under its new meaning, distinct citing discussions that a guest could read.
 *
 * Written against the query builder rather than the extension's own classes,
 * so this keeps working whatever those classes become.
 */
return [
    'up' => function (Builder $schema) {
        $db = $schema->getConnection();

        $db->table('post_references')
            ->whereColumn('source_discussion_id', 'target_discussion_id')
            ->delete();

        $counts = $db->table('post_references')
            ->whereNotNull('post_references.target_discussion_id')
            ->whereNull('post_references.target_deleted_at')
            ->whereExists(fn (QueryBuilder $query) => $query
                ->selectRaw('1')
                ->from('discussions')
                ->whereColumn('discussions.id', 'post_references.source_discussion_id')
                ->whereNull('discussions.hidden_at')
                ->where('discussions.is_private', false))
            ->where(fn (QueryBuilder $query) => $query
                ->whereNull('post_references.source_post_id')
                ->orWhereExists(fn (QueryBuilder $query) => $query
                    ->selectRaw('1')
                    ->from('posts')
                    ->whereColumn('posts.id', 'post_references.source_post_id')
                    ->whereNull('posts.hidden_at')
                    ->where('posts.is_private', false)))
            ->groupBy('post_references.target_discussion_id')
            ->select('post_references.target_discussion_id')
            // Bare inside raw SQL: the query reads post_references alone, and
            // a bare column needs no table prefix.
            ->selectRaw('count(distinct source_discussion_id) as total')
            ->get();

        $db->table('discussions')->where('references_count', '!=', 0)->update(['references_count' => 0]);

        foreach ($counts as $row) {
            $db->table('discussions')
                ->where('id', $row->target_discussion_id)
                ->update(['references_count' => (int) $row->total]);
        }
    },

    'down' => function (Builder $schema) {
        // The rows dropped were never meant to exist, and nothing recreates
        // them: the syncer refuses them now.
    },
];
