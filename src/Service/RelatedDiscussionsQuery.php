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
use Datlechin\References\Settings\Config;
use Flarum\Discussion\Discussion;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Co-citation: two discussions are related when the same discussions keep
 * citing both of them.
 *
 * A plain join and group by, no CTE and no window function, because support
 * for those still differs across MySQL, MariaDB, Postgres and SQLite. Read
 * from {@see Reference::scopeCounted()} only, so a hidden post cannot make two
 * discussions look related to everybody else.
 */
final class RelatedDiscussionsQuery
{
    public function __construct(
        private Config $config,
    ) {
    }

    /**
     * The ranking, the same for every reader and so the part worth caching.
     * Ranked wider than the list is drawn, because the visibility filter in
     * {@see self::visible()} subtracts from it.
     *
     * @return list<int>
     */
    public function rank(int $discussionId): array
    {
        if (! $this->config->relatedDiscussionsEnabled()) {
            return [];
        }

        // Bounded before the join, and the most recent first, so a discussion
        // cited by thousands of sources reads the ones citing it lately rather
        // than whichever the database happens to return.
        $citing = $this->ids($this->counted()
            ->where('post_references.target_discussion_id', $discussionId)
            ->groupBy('post_references.source_discussion_id')
            ->orderByRaw('max(id) desc')
            ->limit($this->config->relatedMaxCandidates())
            ->pluck('post_references.source_discussion_id'));

        if ($citing === []) {
            return [];
        }

        // Anything already citing this discussion, or cited by it, is in the
        // reference list right above. Repeating it here made two sections say
        // one thing. Subqueries rather than id lists, because a hub discussion
        // is cited by more discussions than a query should carry inline.
        return $this->ids($this->counted()
            ->whereIn('post_references.source_discussion_id', $citing)
            ->whereNotNull('post_references.target_discussion_id')
            ->where('post_references.target_discussion_id', '!=', $discussionId)
            ->whereNotIn('post_references.target_discussion_id', fn (QueryBuilder $query) => $query
                ->select('source_discussion_id')
                ->from('post_references')
                ->where('target_discussion_id', $discussionId))
            ->whereNotIn('post_references.target_discussion_id', fn (QueryBuilder $query) => $query
                ->select('target_discussion_id')
                ->from('post_references')
                ->where('source_discussion_id', $discussionId)
                ->whereNotNull('target_discussion_id'))
            ->groupBy('post_references.target_discussion_id')
            ->orderByRaw('count(distinct source_discussion_id) desc')
            ->orderByRaw('max(id) desc')
            ->limit(min($this->config->relatedMaxCandidates(), $this->config->relatedDiscussionsLimit() * 4))
            ->pluck('post_references.target_discussion_id'));
    }

    /**
     * @param list<int> $ranked
     * @return Collection<int, Discussion>
     */
    public function visible(array $ranked, User $actor): Collection
    {
        if ($ranked === []) {
            return new Collection;
        }

        $position = array_flip($ranked);

        /** @var Collection<int, Discussion> $discussions */
        $discussions = Discussion::whereVisibleTo($actor)->whereIn('id', $ranked)->get();

        return $discussions
            ->sortBy(fn (Discussion $discussion) => $position[(int) $discussion->id] ?? PHP_INT_MAX)
            ->take($this->config->relatedDiscussionsLimit())
            ->values();
    }

    /**
     * Raw SQL in here names columns bare: every query reads post_references
     * alone, so nothing is ambiguous, and a bare column needs no table prefix.
     */
    private function counted(): QueryBuilder
    {
        return Reference::query()->counted()->toBase();
    }

    /**
     * @param iterable<mixed> $values
     * @return list<int>
     */
    private function ids(iterable $values): array
    {
        $ids = [];

        foreach ($values as $value) {
            if (is_numeric($value)) {
                $ids[] = (int) $value;
            }
        }

        return $ids;
    }
}
