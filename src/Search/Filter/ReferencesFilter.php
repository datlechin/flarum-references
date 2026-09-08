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

use Datlechin\References\Reference;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;

/**
 * `references:12` finds the discussions that cite discussion 12.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
final class ReferencesFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'references';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $ids = array_map(intval(...), $this->asStringArray($value));

        $sources = Reference::query()
            ->where('target_type', Reference::TARGET_DISCUSSION)
            ->whereIn('target_id', $ids)
            ->select('source_discussion_id');

        $state->getQuery()->whereIn('discussions.id', $sources, 'and', $negate);
    }
}
