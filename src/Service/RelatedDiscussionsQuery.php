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

use Datlechin\References\Settings\Config;
use Flarum\Discussion\Discussion;
use Flarum\User\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Co-citation: two discussions are related when the same discussions keep
 * citing both of them.
 *
 * A plain join and group by, no CTE and no window function, because support
 * for those still differs across MySQL, MariaDB, Postgres and SQLite.
 */
final class RelatedDiscussionsQuery
{
    public function __construct(
        private ConnectionInterface $db,
        private Config $config,
    ) {
    }

    /**
     * @return Collection<int, Discussion>
     */
    public function get(int $discussionId, User $actor): Collection
    {
        if (! $this->config->relatedDiscussionsEnabled()) {
            /** @var Collection<int, Discussion> */
            return Discussion::query()->whereRaw('1 = 0')->get();
        }

        // Bounded before the join, so a discussion cited by thousands of link
        // heavy sources cannot turn this into a full scan.
        $citing = $this->db->table('post_references')
            ->where('target_discussion_id', $discussionId)
            ->distinct()
            ->limit($this->config->relatedMaxCandidates())
            ->pluck('source_discussion_id');

        if ($citing->isEmpty()) {
            /** @var Collection<int, Discussion> */
            return Discussion::query()->whereRaw('1 = 0')->get();
        }

        // Anything already citing this discussion, or cited by it, is shown in
        // the reference list right above. Repeating it here made two sections
        // say one thing.
        $citedBy = $this->db->table('post_references')
            ->where('target_discussion_id', $discussionId)
            ->pluck('source_discussion_id');

        $cites = $this->db->table('post_references')
            ->where('source_discussion_id', $discussionId)
            ->whereNotNull('target_discussion_id')
            ->pluck('target_discussion_id');

        $direct = $citedBy->merge($cites)->push($discussionId)->unique()->all();

        $weights = $this->db->table('post_references')
            ->whereIn('source_discussion_id', $citing)
            ->whereNotNull('target_discussion_id')
            ->whereNotIn('target_discussion_id', $direct)
            ->groupBy('target_discussion_id')
            ->orderByDesc($this->db->raw('count(distinct source_discussion_id)'))
            // Ranked wider than the list is drawn, because the visibility
            // filter below subtracts from whatever comes back. Cutting to the
            // limit first meant a reader who could not open the top entry got a
            // short list rather than the next one down.
            ->limit(min(
                $this->config->relatedMaxCandidates(),
                $this->config->relatedDiscussionsLimit() * 4
            ))
            ->pluck('target_discussion_id');

        if ($weights->isEmpty()) {
            /** @var Collection<int, Discussion> */
            return Discussion::query()->whereRaw('1 = 0')->get();
        }

        /** @var Collection<int, Discussion> $discussions */
        $discussions = Discussion::whereVisibleTo($actor)
            ->whereIn('id', $weights)
            ->get();

        $order = [];

        foreach ($weights->all() as $position => $id) {
            if (is_numeric($id)) {
                $order[(int) $id] = $position;
            }
        }

        return $discussions
            ->sortBy(fn (Discussion $discussion) => $order[$discussion->id] ?? PHP_INT_MAX)
            ->take($this->config->relatedDiscussionsLimit())
            ->values();
    }
}
