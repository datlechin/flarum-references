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

class ReferencedEventPost extends AbstractEventPost implements MergeableInterface
{
    public static string $type = 'discussionReferenced';

    public function saveAfter(?Post $previous = null): static
    {
        // A busy discussion can collect a dozen citations in an hour. Merging
        // them keeps the stream readable instead of turning it into a log.
        if ($previous instanceof static) {
            $previous->content = [
                'sourcePostIds' => array_values(array_unique(array_merge(
                    $previous->content['sourcePostIds'] ?? [],
                    $this->content['sourcePostIds'] ?? [],
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
     * @param list<int> $sourcePostIds
     */
    public static function reply(int $discussionId, ?int $userId, array $sourcePostIds): static
    {
        $post = new static;

        $post->content = ['sourcePostIds' => $sourcePostIds];
        $post->created_at = Carbon::now();
        $post->discussion_id = $discussionId;
        $post->user_id = $userId;

        return $post;
    }
}
