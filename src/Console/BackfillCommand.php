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

use Datlechin\References\Service\ReferenceSyncer;
use Flarum\Post\CommentPost;
use Illuminate\Console\Command;

/**
 * Domain events are queued by `raise()` and released only in the API layer, so
 * a post written any other way records nothing. This is the recovery path, and
 * the one every install needs once after enabling the extension.
 */
final class BackfillCommand extends Command
{
    protected $signature = 'references:backfill {--chunk=500 : Posts to process per batch} {--from-id= : Resume from this post ID}';

    protected $description = 'Record references for posts written before the extension was enabled.';

    public function handle(ReferenceSyncer $syncer): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $fromId = $this->option('from-id') !== null ? (int) $this->option('from-id') : 0;

        $query = CommentPost::query()->where('id', '>=', $fromId)->orderBy('id');

        $bar = $this->output->createProgressBar((clone $query)->count());
        $bar->start();

        $posts = 0;
        $rows = 0;

        $query->chunkById($chunkSize, function ($chunk) use ($syncer, &$posts, &$rows, $bar) {
            foreach ($chunk as $post) {
                $posts++;
                $bar->advance();

                // A historical pass must never delete: a target it cannot
                // resolve today, because the extension owning it is off, would
                // take real rows with it.
                $rows += count($syncer->sync($post, deleteOrphans: false));
            }
        }, 'id', 'id');

        $bar->finish();
        $this->newLine();
        $this->info("Scanned $posts posts, recorded $rows references.");

        return self::SUCCESS;
    }
}
