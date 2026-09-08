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
 * `referenced-by:12` finds the discussions that discussion 12 cites.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
final class ReferencedByFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'referencedBy';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $ids = array_map(intval(...), $this->asStringArray($value));

        // Scoped, because the extension's own rule is that a reference is
        // visible only when both of its ends are. Unscoped, the filter answers
        // questions about a discussion the reader cannot open.
        $targets = Reference::whereVisibleTo($state->getActor())
            ->where('target_type', Reference::TARGET_DISCUSSION)
            ->whereIn('source_discussion_id', $ids)
            ->select('target_id');

        $state->getQuery()->whereIn('discussions.id', $targets, 'and', $negate);
    }
}
