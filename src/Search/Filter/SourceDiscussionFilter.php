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

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
final class SourceDiscussionFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'sourceDiscussion';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $ids = array_map(intval(...), $this->asStringArray($value));

        $state->getQuery()->whereIn('post_references.source_discussion_id', $ids, 'and', $negate);
    }
}
