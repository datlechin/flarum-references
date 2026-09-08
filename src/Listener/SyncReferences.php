<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Listener;

use Datlechin\References\Job\SendReferenceNotifications;
use Datlechin\References\Reference;
use Datlechin\References\Service\EventPostWriter;
use Datlechin\References\Service\ReferenceSyncer;
use Flarum\Approval\Event\PostWasApproved;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Revised;
use Illuminate\Contracts\Queue\Queue;

final class SyncReferences
{
    public function __construct(
        private ReferenceSyncer $syncer,
        private EventPostWriter $eventPosts,
        private Queue $queue,
    ) {
    }

    public function handle(Posted|Revised|Restored|PostWasApproved $event): void
    {
        // Only an edit can orphan a row. The others are a post arriving or
        // becoming visible again, where there is nothing yet to remove.
        $created = $this->syncer->sync($event->post, deleteOrphans: $event instanceof Revised);

        if ($created !== []) {
            $this->eventPosts->write($created);
        }

        // A post that becomes visible later wrote its rows back when it was
        // posted, so nothing is created here and returning early on that left
        // the two events below doing nothing at all: a restored post's alert
        // stayed retracted, and a post held for approval announced itself while
        // nobody could see it and then never again. Re-sending the whole set is
        // safe because NotificationSyncer un-deletes a recipient's existing row
        // rather than adding a second one.
        $ids = $event instanceof Posted || $event instanceof Revised
            ? array_map(fn (Reference $reference) => (int) $reference->id, $created)
            : array_values(
                Reference::query()
                    ->where('source_post_id', $event->post->id)
                    ->get(['id'])
                    ->map(fn (Reference $reference) => (int) $reference->id)
                    ->all()
            );

        if ($ids !== []) {
            $this->queue->push(new SendReferenceNotifications($ids));
        }
    }
}
