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

        if ($created === []) {
            return;
        }

        $this->eventPosts->write($created);

        $this->queue->push(new SendReferenceNotifications(
            array_map(fn (Reference $reference) => $reference->id, $created),
        ));
    }
}
