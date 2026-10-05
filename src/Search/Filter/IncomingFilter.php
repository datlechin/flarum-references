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
 * `filter[incoming]=12`. Everything citing one discussion from elsewhere, one row per citing discussion. The full list behind the sidebar's "Show all", grouped the way the sidebar is.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
final class IncomingFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function __construct(
        private DiscussionReferenceQuery $references,
    ) {
    }

    public function getFilterKey(): string
    {
        return 'incoming';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $discussionId = $this->asInt($value);
        $rows = $this->references->incomingRows($discussionId, $state->getActor());

        $state->getQuery()->whereIn(
            'post_references.id',
            $this->references->representatives($rows, 'post_references.source_discussion_id'),
            'and',
            $negate,
        );
    }
}
