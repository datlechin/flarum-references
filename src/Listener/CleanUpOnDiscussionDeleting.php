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

use Datlechin\References\Reference;
use Datlechin\References\Service\BrokenReferenceMarker;
use Datlechin\References\Service\ReferenceCounter;
use Datlechin\References\Service\ReferenceNotifier;
use Flarum\Discussion\Event\Deleting;

/**
 * Every cleanup concern for a discussion belongs here, not in the per post
 * listeners. Deleting a discussion removes its posts through the database's
 * own cascade, which never reaches the API layer, so no `Post\Event\Deleted`
 * fires for any of them. Anything added to those listeners later will silently
 * never run for a discussion level delete.
 */
final class CleanUpOnDiscussionDeleting
{
    public function __construct(
        private BrokenReferenceMarker $marker,
        private ReferenceCounter $counter,
        private ReferenceNotifier $notifier,
    ) {
    }

    public function handle(Deleting $event): void
    {
        $discussionId = (int) $event->discussion->id;

        $this->notifier->retractForDiscussion($discussionId);
        $this->marker->markDiscussion($discussionId);

        $outgoing = Reference::query()->where('source_discussion_id', $discussionId);

        $decrements = [];

        foreach ($outgoing->get(['id', 'target_discussion_id', 'target_deleted_at']) as $row) {
            if ($row->target_discussion_id !== null && $row->target_discussion_id !== $discussionId && ! $row->isBroken()) {
                $decrements[$row->target_discussion_id] = ($decrements[$row->target_discussion_id] ?? 0) - 1;
            }
        }

        $outgoing->delete();

        $this->counter->apply($decrements);
    }
}
