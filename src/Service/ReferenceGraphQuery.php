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

/**
 * Breadth first, in PHP, two queries per hop.
 *
 * A recursive CTE would be one query, but support differs enough across the
 * four databases Flarum runs on that it is not worth the reach. Each hop is
 * capped, so a hub discussion truncates by recency rather than returning a
 * payload nothing can draw.
 */
final class ReferenceGraphQuery
{
    public function __construct(
        private ConnectionInterface $db,
        private Config $config,
    ) {
    }

    /**
     * @return array{nodes: list<array{id: int, title: string, count: int}>, edges: list<array{from: int, to: int}>}
     */
    public function get(int $discussionId, User $actor): array
    {
        $depth = $this->config->graphMaxDepth();
        $perHop = $this->config->graphMaxPerHop();

        $visited = [$discussionId => true];
        $frontier = [$discussionId];
        $edges = [];

        for ($hop = 0; $hop < $depth && $frontier !== []; $hop++) {
            $next = [];

            foreach ($this->edgesFor($frontier, $perHop) as $edge) {
                $key = $edge['from'].'>'.$edge['to'];
                $edges[$key] = $edge;

                foreach ([$edge['from'], $edge['to']] as $id) {
                    if (! isset($visited[$id])) {
                        $visited[$id] = true;
                        $next[] = $id;
                    }
                }
            }

            $frontier = $next;
        }

        $discussions = Discussion::whereVisibleTo($actor)
            ->whereIn('id', array_keys($visited))
            ->get(['id', 'title', 'references_count']);

        $nodes = [];

        foreach ($discussions as $discussion) {
            $nodes[] = [
                'id' => (int) $discussion->id,
                'title' => $discussion->title,
                'count' => (int) $discussion->references_count,
            ];
        }

        $visible = array_column($nodes, 'id');

        $edges = array_values(array_filter(
            $edges,
            fn (array $edge) => in_array($edge['from'], $visible, true) && in_array($edge['to'], $visible, true),
        ));

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * @param list<int> $ids
     * @return list<array{from: int, to: int}>
     */
    protected function edgesFor(array $ids, int $limit): array
    {
        $out = $this->db->table('post_references')
            ->whereIn('source_discussion_id', $ids)
            ->whereNotNull('target_discussion_id')
            ->whereNull('target_deleted_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['source_discussion_id', 'target_discussion_id']);

        $in = $this->db->table('post_references')
            ->whereIn('target_discussion_id', $ids)
            ->whereNull('target_deleted_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['source_discussion_id', 'target_discussion_id']);

        $edges = [];

        foreach ($out->merge($in) as $row) {
            $from = (int) $row->source_discussion_id;
            $to = (int) $row->target_discussion_id;

            if ($from !== $to) {
                $edges[] = ['from' => $from, 'to' => $to];
            }
        }

        return $edges;
    }
}
