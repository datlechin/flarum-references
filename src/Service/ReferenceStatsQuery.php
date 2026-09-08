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
use Illuminate\Database\ConnectionInterface;

/**
 * Reads the daily rollup, never the edge table, so a year long report costs
 * the same as a week long one.
 */
final class ReferenceStatsQuery
{
    public function __construct(
        private ConnectionInterface $db,
    ) {
    }

    /**
     * @return list<array{date: string, count: int}>
     */
    public function daily(Carbon $from, Carbon $to): array
    {
        // The rollup column is `total` rather than `count` so this stays a
        // plain aggregate: `count` is reserved almost everywhere and quoting
        // it portably would mean building the expression by hand.
        $rows = $this->db->table('reference_daily')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('date')
            ->orderBy('date')
            ->get(['date', $this->db->raw('sum(total) as total')]);

        $series = [];

        foreach ($rows as $row) {
            $series[] = ['date' => (string) $row->date, 'count' => (int) $row->total];
        }

        return $series;
    }

    /**
     * @return array<string, int>
     */
    public function byRelationType(): array
    {
        $counts = [];

        foreach ($this->db->table('post_references')->groupBy('relation_type')->get([
            'relation_type',
            $this->db->raw('count(*) as total'),
        ]) as $row) {
            $counts[(string) $row->relation_type] = (int) $row->total;
        }

        return $counts;
    }

    /**
     * @return array<string, int>
     */
    public function byOrigin(): array
    {
        $counts = [];

        foreach ($this->db->table('post_references')->groupBy('origin')->get([
            'origin',
            $this->db->raw('count(*) as total'),
        ]) as $row) {
            $counts[(string) $row->origin] = (int) $row->total;
        }

        return $counts;
    }

    public function totals(): array
    {
        return [
            'references' => Reference::query()->count(),
            'broken' => Reference::query()->whereNotNull('target_deleted_at')->count(),
            'manual' => Reference::query()->whereNull('source_post_id')->count(),
        ];
    }
}
