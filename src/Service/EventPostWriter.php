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

/**
 * "Alice referenced this discussion from <where>", written into the discussion
 * that was referenced.
 *
 * Only for a citation written somewhere a guest could in principle read. The
 * line is shown to everybody who can open the referenced discussion, and a
 * line saying a member cited it from a hidden or unapproved post tells all of
 * them the post exists.
 */
final class EventPostWriter
{
    public function __construct(
        private Config $config,
    ) {
    }

    /**
     * @param iterable<Reference> $references
     */
    public function write(iterable $references): void
    {
        if (! $this->config->eventPostEnabled()) {
            return;
        }

        $bySource = [];

        foreach ($references as $reference) {
            // A manual reference was not written by anybody's post.
            if ($reference->target_discussion_id === null || $reference->source_post_id === null) {
                continue;
            }

            $bySource[$reference->source_post_id][$reference->target_discussion_id] = true;
        }

        foreach ($bySource as $sourcePostId => $targetDiscussionIds) {
            $source = Post::query()->with('discussion')->find($sourcePostId);

            if (! $source instanceof Post || ! $this->isPublic($source)) {
                continue;
            }

            foreach (array_keys($targetDiscussionIds) as $discussionId) {
                $discussion = Discussion::query()->find($discussionId);

                if (! $discussion instanceof Discussion) {
                    continue;
                }

                $discussion->mergePost(ReferencedEventPost::reply($discussionId, $source));
                $discussion->save();
            }
        }
    }

    private function isPublic(Post $source): bool
    {
        $discussion = $source->discussion;

        return ! $source->is_private
            && $source->hidden_at === null
            && $discussion instanceof Discussion
            && ! $discussion->is_private
            && $discussion->hidden_at === null;
    }
}
