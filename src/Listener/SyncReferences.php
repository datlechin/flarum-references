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
use Datlechin\References\Service\ReferenceCounter;
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
        private ReferenceCounter $counter,
        private Queue $queue,
    ) {
    }

    public function handle(Posted|Revised|Restored|PostWasApproved $event): void
    {
        $post = $event->post;

        // Only an edit can orphan a row. The others are a post arriving or
        // becoming visible again, where there is nothing yet to remove.
        $created = $this->syncer->sync($post, deleteOrphans: $event instanceof Revised);

        if ($event instanceof Posted || $event instanceof Revised) {
            // A post held for approval is announced when it is approved, not
            // while nobody can read it.
            if (! $post->is_private) {
                $this->eventPosts->write($created);
            }

            $this->notify($created);

            return;
        }

        // A post becoming visible again wrote its rows long ago, so nothing is
        // created here. The counter only counts what a guest can read, so it
        // moves; the notifications are sent again, which is safe because
        // NotificationSyncer un-deletes a recipient's existing row rather than
        // adding a second one.
        $rows = Reference::query()->where('source_post_id', $post->id)->get();

        $this->counter->refresh($rows->pluck('target_discussion_id'));

        // Approval is the first moment anybody could read the post, so it is
        // also the first moment to announce it. A restore was announced before
        // it was hidden.
        if ($event instanceof PostWasApproved) {
            $this->eventPosts->write($rows);
        }

        $this->notify($rows->all());
    }

    /**
     * @param iterable<Reference> $references
     */
    private function notify(iterable $references): void
    {
        $ids = [];

        foreach ($references as $reference) {
            $ids[] = (int) $reference->id;
        }

        if ($ids !== []) {
            $this->queue->push(new SendReferenceNotifications($ids));
        }
    }
}
