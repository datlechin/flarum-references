<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Console;

use Carbon\Carbon;
use Datlechin\References\Settings\Config;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

/**
 * Walks forward from a stored cursor and rewrites one day at a time, so a
 * rerun repairs a day rather than doubling it.
 */
final class BuildDailyRollupCommand extends Command
{
    private const CURSOR = Config::PREFIX.'daily_rollup_last_date';

    protected $signature = 'references:build-daily-rollup {--rebuild : Discard the cursor and rebuild every day}';

    protected $description = 'Aggregate references into the daily rollup table.';

    public function __construct(
        private ConnectionInterface $db,
        private SettingsRepositoryInterface $settings,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $yesterday = Carbon::yesterday();
        $from = $this->startDate();

        if ($from === null || $from->greaterThan($yesterday)) {
            $this->info('Nothing to roll up.');

            return self::SUCCESS;
        }

        $days = 0;

        for ($date = $from->copy(); $date->lessThanOrEqualTo($yesterday); $date->addDay()) {
            $this->rollUp($date);
            $days++;
        }

        $this->settings->set(self::CURSOR, $yesterday->toDateString());
        $this->info("Rolled up $days days.");

        return self::SUCCESS;
    }

    protected function rollUp(Carbon $date): void
    {
        $this->db->transaction(function () use ($date) {
            $this->db->table('reference_daily')->where('date', $date->toDateString())->delete();

            $rows = $this->db->table('post_references')
                ->whereNotNull('target_discussion_id')
                ->whereBetween('created_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
                ->groupBy('target_discussion_id')
                // Aliased: an un-aliased `count(*)` comes back under a
                // different name on Postgres than on MySQL.
                ->pluck($this->db->raw('count(*) as total'), 'target_discussion_id');

            $insert = [];

            foreach ($rows as $discussionId => $count) {
                $insert[] = [
                    'date' => $date->toDateString(),
                    'target_discussion_id' => (int) $discussionId,
                    'total' => is_numeric($count) ? (int) $count : 0,
                ];
            }

            foreach (array_chunk($insert, 500) as $chunk) {
                $this->db->table('reference_daily')->insert($chunk);
            }
        });
    }

    protected function startDate(): ?Carbon
    {
        if ($this->option('rebuild')) {
            $first = $this->db->table('post_references')->min('created_at');

            return is_string($first) ? Carbon::parse($first)->startOfDay() : null;
        }

        $cursor = $this->settings->get(self::CURSOR);

        if (is_string($cursor) && $cursor !== '') {
            return Carbon::parse($cursor)->addDay()->startOfDay();
        }

        $first = $this->db->table('post_references')->min('created_at');

        return is_string($first) ? Carbon::parse($first)->startOfDay() : null;
    }
}
