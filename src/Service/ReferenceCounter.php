<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Service;

use Datlechin\References\Reference;
use Illuminate\Database\ConnectionInterface;

/**
 * `discussions.references_count`: how many other discussions cite this one.
 *
 * Distinct discussions rather than rows, so one thread linking the same place
 * forty times is one citation and cannot carry it up the ranking on its own.
 * Counted only over {@see Reference::scopeCounted()}, the rows a guest could
 * in principle read.
 *
 * Recomputed for the discussions a change touched, never nudged by a delta.
 * Core keeps `comment_count` the same way, and a recount cannot drift: the
 * old increments double counted under a race, and the clamp that stopped a
 * decrement going negative hid the drift instead of repairing it.
 */
final class ReferenceCounter
{
    public function __construct(
        private ConnectionInterface $db,
    ) {
    }

    /**
     * @param iterable<mixed> $discussionIds as read back from the database, so
     *                                       nulls and duplicates are expected
     */
    public function refresh(iterable $discussionIds): void
    {
        $ids = [];

        foreach ($discussionIds as $id) {
            if (is_numeric($id)) {
                $ids[(int) $id] = (int) $id;
            }
        }

        foreach (array_chunk(array_values($ids), 500) as $chunk) {
            $counts = $this->count($chunk);

            foreach ($chunk as $id) {
                $this->db->table('discussions')
                    ->where('id', $id)
                    ->update(['references_count' => $counts[$id] ?? 0]);
            }
        }
    }

    /**
     * Every discussion with at least one counted citation. A discussion that
     * is absent here counts zero.
     *
     * @param list<int>|null $discussionIds null for all of them
     * @return array<int, int>
     */
    public function count(?array $discussionIds = null): array
    {
        $query = Reference::query()->counted()->toBase();

        if ($discussionIds !== null) {
            $query->whereIn('post_references.target_discussion_id', $discussionIds);
        } else {
            $query->whereNotNull('post_references.target_discussion_id');
        }

        // Unqualified inside the raw SQL: the query reads post_references
        // alone, so the name cannot be ambiguous, and a bare column needs no
        // table prefix. Aliased because an un-aliased aggregate comes back
        // under a different name on each database.
        $rows = $query
            ->groupBy('post_references.target_discussion_id')
            ->select('post_references.target_discussion_id')
            ->selectRaw('count(distinct source_discussion_id) as total')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(int) $row->target_discussion_id] = (int) $row->total;
        }

        return $counts;
    }
}
