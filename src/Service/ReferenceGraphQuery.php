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
use Illuminate\Database\Query\Builder as QueryBuilder;

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
        private Config $config,
    ) {
    }

    /**
     * The walk itself, which is the expensive half and depends on nothing about
     * the reader. Kept apart from the titles so it can be cached once for
     * everybody rather than once per reader.
     *
     * @return array{ids: list<int>, edges: list<array{from: int, to: int}>}
     */
    public function traverse(int $discussionId): array
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

        return ['ids' => array_map('intval', array_keys($visited)), 'edges' => array_values($edges)];
    }

    /**
     * Titles, and the edges between the ones this reader may open. Cheap, one
     * query, and run on every request: which discussions a reader may see is
     * not a function of the groups they belong to, because core also grants a
     * discussion to its own author.
     *
     * Only what the reader can reach from the centre through discussions they
     * may open is kept. A discussion found two steps out through one they
     * cannot see used to stay on the page with no edge to it, which said
     * there was a hidden discussion between them.
     *
     * @param array{ids: list<int>, edges: list<array{from: int, to: int}>} $graph
     * @return array{nodes: list<array{id: int, title: string, count: int}>, edges: list<array{from: int, to: int}>}
     */
    public function visible(array $graph, User $actor, int $centre): array
    {
        $edges = $graph['edges'];

        $discussions = Discussion::whereVisibleTo($actor)
            ->whereIn('id', $graph['ids'])
            ->get(['id', 'title', 'references_count']);

        $nodes = [];

        foreach ($discussions as $discussion) {
            $nodes[] = [
                'id' => (int) $discussion->id,
                'title' => $discussion->title,
                'count' => (int) $discussion->references_count,
            ];
        }

        $visible = array_flip(array_column($nodes, 'id'));

        $edges = array_values(array_filter(
            $edges,
            fn (array $edge) => isset($visible[$edge['from']], $visible[$edge['to']]),
        ));

        $reached = $this->reachable($centre, $edges);

        return [
            'nodes' => array_values(array_filter($nodes, fn (array $node) => isset($reached[$node['id']]))),
            'edges' => array_values(array_filter($edges, fn (array $edge) => isset($reached[$edge['from']]))),
        ];
    }

    /**
     * @param list<array{from: int, to: int}> $edges
     * @return array<int, true>
     */
    private function reachable(int $centre, array $edges): array
    {
        $neighbours = [];

        foreach ($edges as $edge) {
            $neighbours[$edge['from']][] = $edge['to'];
            $neighbours[$edge['to']][] = $edge['from'];
        }

        $reached = [$centre => true];
        $queue = [$centre];

        while ($queue !== []) {
            foreach ($neighbours[array_shift($queue)] ?? [] as $next) {
                if (! isset($reached[$next])) {
                    $reached[$next] = true;
                    $queue[] = $next;
                }
            }
        }

        return $reached;
    }

    /**
     * One edge per pair of discussions, however many posts in one cite the
     * other: counting rows let a single chatty thread use up a whole hop.
     * Read from {@see Reference::scopeCounted()}, so a hidden post draws no
     * line.
     *
     * @param list<int> $ids
     * @return list<array{from: int, to: int}>
     */
    protected function edgesFor(array $ids, int $limit): array
    {
        $out = $this->pairs($limit)->whereIn('post_references.source_discussion_id', $ids)->get();
        $in = $this->pairs($limit)->whereIn('post_references.target_discussion_id', $ids)->get();

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

    private function pairs(int $limit): QueryBuilder
    {
        return Reference::query()->counted()->toBase()
            ->whereNotNull('post_references.target_discussion_id')
            ->groupBy('post_references.source_discussion_id', 'post_references.target_discussion_id')
            ->select('post_references.source_discussion_id', 'post_references.target_discussion_id')
            ->orderByRaw('max(id) desc')
            ->limit($limit);
    }
}
