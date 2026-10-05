<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Search\Filter;

use Datlechin\References\Service\DiscussionReferenceQuery;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;

/**
 * `filter[outgoing]=12`. Everything one discussion cites elsewhere, one row per cited discussion.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
final class OutgoingFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function __construct(
        private DiscussionReferenceQuery $references,
    ) {
    }

    public function getFilterKey(): string
    {
        return 'outgoing';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $discussionId = $this->asInt($value);
        $rows = $this->references->outgoingRows($discussionId, $state->getActor());

        $state->getQuery()->whereIn(
            'post_references.id',
            $this->references->representatives($rows, 'post_references.target_discussion_id'),
            'and',
            $negate,
        );
    }
}
