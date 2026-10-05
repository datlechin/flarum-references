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
use Datlechin\References\RelationType;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * What a discussion's sidebar lists: one row per OTHER discussion, in each
 * direction, out of the rows the reader may see.
 *
 * A discussion is cited by discussions, not by rows. Listing rows let one
 * thread that linked here five times fill the whole preview, made the count
 * say five where the list showed one, and drew the same title five times over
 * in the full list.
 *
 * The row standing for a discussion is the one a moderator classified, when
 * there is one, so "duplicate of" is not lost behind a later plain link, and
 * otherwise the latest.
 */
final class DiscussionReferenceQuery
{
    /**
     * @return Builder<Reference>
     */
    public function incoming(int $discussionId, User $actor): Builder
    {
        return Reference::query()->whereIn(
            'post_references.id',
            $this->representatives($this->incomingRows($discussionId, $actor), 'post_references.source_discussion_id'),
        );
    }

    /**
     * @return Builder<Reference>
     */
    public function outgoing(int $discussionId, User $actor): Builder
    {
        return Reference::query()->whereIn(
            'post_references.id',
            $this->representatives($this->outgoingRows($discussionId, $actor), 'post_references.target_discussion_id'),
        );
    }

    public function incomingCount(int $discussionId, User $actor): int
    {
        return $this->incomingRows($discussionId, $actor)->distinct()->count('post_references.source_discussion_id');
    }

    public function outgoingCount(int $discussionId, User $actor): int
    {
        return $this->outgoingRows($discussionId, $actor)->distinct()->count('post_references.target_discussion_id');
    }

    /**
     * Narrows a query over visible rows to one row per value of a column.
     * Applied by the search filters too, so the full list in the modal groups
     * exactly as the sidebar preview does.
     *
     * @param Builder<Reference> $rows
     */
    public function representatives(Builder $rows, string $groupColumn): QueryBuilder
    {
        // Unqualified inside the raw SQL: the rows are read from
        // post_references alone, and a bare column needs no table prefix.
        return $rows->toBase()
            ->select([])
            ->selectRaw(
                'coalesce(max(case when relation_type <> ? or note is not null then id end), max(id))',
                [RelationType::References->value],
            )
            ->groupBy($groupColumn);
    }

    /**
     * @return Builder<Reference>
     */
    public function incomingRows(int $discussionId, User $actor): Builder
    {
        return Reference::whereVisibleTo($actor)
            ->where('post_references.target_discussion_id', $discussionId)
            ->where('post_references.source_discussion_id', '!=', $discussionId)
            ->whereNull('post_references.target_deleted_at');
    }

    /**
     * @return Builder<Reference>
     */
    public function outgoingRows(int $discussionId, User $actor): Builder
    {
        return Reference::whereVisibleTo($actor)
            ->where('post_references.source_discussion_id', $discussionId)
            ->whereNotNull('post_references.target_discussion_id')
            ->where('post_references.target_discussion_id', '!=', $discussionId)
            ->whereNull('post_references.target_deleted_at');
    }
}
