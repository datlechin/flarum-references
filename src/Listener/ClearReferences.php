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
use Datlechin\References\Service\ReferenceNotifier;
use Datlechin\References\Service\ReferenceSyncer;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Deleting;
use Flarum\Post\Event\Hidden;

final class ClearReferences
{
    public function __construct(
        private ReferenceSyncer $syncer,
        private ReferenceNotifier $notifier,
        private BrokenReferenceMarker $marker,
    ) {
    }

    public function handle(Hidden|Deleting|Deleted $event): void
    {
        // By `Deleted` the post row is gone, and `source_post_id` cascades, so
        // the database has already removed every row the other two branches
        // work from. All that is left to do is mark what pointed AT the post,
        // which has no key to the post and so survives.
        if ($event instanceof Deleted) {
            $this->marker->markTarget(Reference::TARGET_POST, (int) $event->post->id);

            return;
        }

        $this->notifier->retractFor($event->post);

        // Hiding is reversible, so the rows stay: a reader cannot see them
        // either way, because a reference is only as visible as its two ends.
        // Deleting them and rebuilding on restore would throw away any
        // classification a moderator had put on them, which is the one thing
        // an edit is careful not to do.
        if ($event instanceof Deleting) {
            $this->syncer->clear($event->post);
        }
    }
}
