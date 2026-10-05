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

use Datlechin\References\Service\ReferenceCounter;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

/**
 * Every change recounts the discussions it touched, so this has little to do.
 * It is still run daily, because rows also change in ways no event reports:
 * a hand edit, a restore from backup, a post moved by another extension.
 */
final class ReconcileCountersCommand extends Command
{
    protected $signature = 'references:reconcile {--chunk=1000 : Discussions to correct per batch}';

    protected $description = 'Recompute discussions.references_count from the reference rows.';

    public function __construct(
        private ConnectionInterface $db,
        private ReferenceCounter $counter,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));

        $truth = $this->counter->count();

        $corrected = 0;

        $this->db->table('discussions')
            ->select('id', 'references_count')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($discussions) use ($truth, &$corrected) {
                foreach ($discussions as $discussion) {
                    $actual = $truth[(int) $discussion->id] ?? 0;
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
