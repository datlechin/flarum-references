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
use Datlechin\References\Service\ReferenceCounter;
use Datlechin\References\Service\ReferenceNotifier;
use Flarum\Discussion\Event\Hidden;
use Flarum\Discussion\Event\Restored;
use Illuminate\Contracts\Queue\Queue;

/**
 * Hiding a discussion hides every post in it without a post event for any of
 * them, so what the per post listeners do on hide and restore is repeated here
 * for the whole discussion.
 */
final class UpdateOnDiscussionVisibility
{
    public function __construct(
        private ReferenceCounter $counter,
        private ReferenceNotifier $notifier,
        private Queue $queue,
    ) {
    }

    public function handle(Hidden|Restored $event): void
    {
        $discussionId = (int) $event->discussion->id;
        $rows = Reference::query()->where('source_discussion_id', $discussionId)->get(['id', 'target_discussion_id']);

        $this->counter->refresh($rows->pluck('target_discussion_id'));

        if ($event instanceof Hidden) {
            $this->notifier->retractForDiscussion($discussionId);
        } elseif ($rows->isNotEmpty()) {
            $this->queue->push(new SendReferenceNotifications(
                array_values($rows->map(fn (Reference $row) => (int) $row->id)->all())
            ));
        }
    }
}
