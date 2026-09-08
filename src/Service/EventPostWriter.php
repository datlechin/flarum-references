<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Service;

use Datlechin\References\Post\ReferencedEventPost;
use Datlechin\References\Reference;
use Datlechin\References\Settings\Config;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;

final class EventPostWriter
{
    public function __construct(
        private Config $config,
    ) {
    }

    /**
     * @param list<Reference> $references
     */
    public function write(array $references): void
    {
        if (! $this->config->eventPostEnabled()) {
            return;
        }

        $byDiscussion = [];

        foreach ($references as $reference) {
            $targetDiscussionId = $reference->target_discussion_id;
            $sourcePostId = $reference->source_post_id;

            // A discussion citing itself has nothing to announce, and a manual
            // reference was not written by anybody's post.
            if (
                $targetDiscussionId === null
                || $sourcePostId === null
                || $targetDiscussionId === $reference->source_discussion_id
            ) {
                continue;
            }

            $byDiscussion[$targetDiscussionId][] = $sourcePostId;
        }

        foreach ($byDiscussion as $discussionId => $sourcePostIds) {
            $discussion = Discussion::query()->find($discussionId);

            if (! $discussion instanceof Discussion) {
                continue;
            }

            $sourcePost = Post::query()->find($sourcePostIds[0]);

            $discussion->mergePost(ReferencedEventPost::reply(
                $discussionId,
                $sourcePost?->user_id,
                array_values(array_unique($sourcePostIds)),
            ));

            $discussion->save();
        }
    }
}
