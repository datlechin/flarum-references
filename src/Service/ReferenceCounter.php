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

use Flarum\Discussion\Discussion;

final class ReferenceCounter
{
    /**
     * @param array<int, int> $byDiscussion discussion id => delta
     */
    public function apply(array $byDiscussion): void
    {
        foreach ($byDiscussion as $discussionId => $delta) {
            if ($delta === 0) {
                continue;
            }

            $query = Discussion::query()->where('id', $discussionId);

            if ($delta > 0) {
                $query->increment('references_count', $delta);
            } else {
                // Clamp, so a drifted counter cannot go negative and stay there.
                $query->where('references_count', '>=', -$delta)->decrement('references_count', -$delta);
            }
        }
    }

    public function increment(?int $discussionId): void
    {
        if ($discussionId !== null) {
            $this->apply([$discussionId => 1]);
        }
    }
}
