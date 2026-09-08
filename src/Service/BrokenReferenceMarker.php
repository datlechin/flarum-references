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

use Carbon\Carbon;
use Datlechin\References\Reference;
use Illuminate\Database\Eloquent\Builder;

/**
 * A target that disappears leaves its rows behind, marked. Deleting them would
 * take the broken link report's only source of truth with them.
 *
 * The ranking counter is defined as the rows that are not broken, which is
 * what `references:reconcile` rebuilds it from, so marking has to move it.
 */
final class BrokenReferenceMarker
{
    public function __construct(
        private ReferenceCounter $counter,
    ) {
    }

    public function markTarget(string $type, int $id): void
    {
        $this->mark(
            Reference::query()
                ->where('target_type', $type)
                ->where('target_id', $id)
        );
    }

    /**
     * Everything pointing into a discussion: the discussion itself, and every
     * post inside it. One statement, because the database tears those posts
     * down with a cascade that fires no events.
     */
    public function markDiscussion(int $discussionId): void
    {
        $this->mark(Reference::query()->where('target_discussion_id', $discussionId));
    }

    /**
     * @param Builder<Reference> $query
     */
    protected function mark(Builder $query): void
    {
        $query->whereNull('target_deleted_at');

        $decrements = [];

        foreach ($query->get(['id', 'target_discussion_id']) as $row) {
            if ($row->target_discussion_id !== null) {
                $decrements[$row->target_discussion_id] = ($decrements[$row->target_discussion_id] ?? 0) - 1;
            }
        }

        if ($decrements === [] && (clone $query)->doesntExist()) {
            return;
        }

        $query->update(['target_deleted_at' => Carbon::now()]);

        $this->counter->apply($decrements);
    }
}
