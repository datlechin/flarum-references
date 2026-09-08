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

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

/**
 * A denormalised counter drifts under concurrent writes, erasure requests and
 * hand edits. That is a when, not an if, which is why this runs daily rather
 * than after the first complaint.
 */
final class ReconcileCountersCommand extends Command
{
    protected $signature = 'references:reconcile {--chunk=1000 : Discussions to correct per batch}';

    protected $description = 'Recompute discussions.references_count from the reference rows.';

    public function __construct(
        private ConnectionInterface $db,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));

        // Aliased because `pluck()` reads the column back by the name it was
        // given, and an un-aliased `count(*)` is called `count(*)` on MySQL and
        // `count` on Postgres. Without the alias this silently plucks nulls and
        // zeroes every counter it was meant to repair.
        $truth = $this->db->table('post_references')
            ->whereNotNull('target_discussion_id')
            ->whereNull('target_deleted_at')
            ->groupBy('target_discussion_id')
            ->pluck($this->db->raw('count(*) as total'), 'target_discussion_id');

        $corrected = 0;

        $this->db->table('discussions')
            ->select('id', 'references_count')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($discussions) use ($truth, &$corrected) {
                foreach ($discussions as $discussion) {
                    $counted = $truth[$discussion->id] ?? 0;
                    $actual = is_numeric($counted) ? (int) $counted : 0;
                    $stored = is_numeric($discussion->references_count) ? (int) $discussion->references_count : 0;

                    if ($stored === $actual) {
                        continue;
                    }

                    $this->db->table('discussions')
                        ->where('id', $discussion->id)
                        ->update(['references_count' => $actual]);

                    $corrected++;
                }
            });

        $this->info("Corrected $corrected discussions.");

        return self::SUCCESS;
    }
}
