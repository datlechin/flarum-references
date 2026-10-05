<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Post;

use Carbon\Carbon;
use Flarum\Post\AbstractEventPost;
use Flarum\Post\MergeableInterface;
use Flarum\Post\Post;

/**
 * Content is `{sourcePostIds: list<int>}`. Which of those a reader may follow
 * is decided when the post is served, through the `referenceSources`
 * relationship, so nothing about the citing discussion is stored here.
 */
class ReferencedEventPost extends AbstractEventPost implements MergeableInterface
{
    public static string $type = 'discussionReferenced';

    public function saveAfter(?Post $previous = null): static
    {
        // A busy discussion can collect a dozen citations in an hour. Merging
        // them keeps the stream readable, but only for the same member, the
        // way core merges renames: the line names one person, and merging a
        // second member's citation into it credited the first with both.
        if ($previous instanceof static && $previous->user_id === $this->user_id) {
            $previous->content = [
                'sourcePostIds' => array_values(array_unique(array_merge(
                    $previous->sourcePostIds(),
                    $this->sourcePostIds(),
                ))),
            ];
            $previous->created_at = $this->created_at;
            $previous->save();

            return $previous;
        }

        $this->save();

        return $this;
    }

    /**
     * @return list<int>
     */
    public function sourcePostIds(): array
    {
        $content = $this->content;
        $ids = is_array($content) && is_array($content['sourcePostIds'] ?? null) ? $content['sourcePostIds'] : [];

        return array_values(array_map(intval(...), array_filter($ids, is_numeric(...))));
    }

    public static function reply(int $discussionId, Post $source): static
    {
        $post = new static;

        $post->content = ['sourcePostIds' => [(int) $source->id]];
        $post->created_at = Carbon::now();
        $post->discussion_id = $discussionId;
        $post->user_id = $source->user_id;

        return $post;
    }
}
