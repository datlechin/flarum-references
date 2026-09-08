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

/**
 * `has:references`. Reads the denormalised column, so it costs no join.
 *
 * A separate key from `references:12`: FilterManager keeps a list per key, so
 * two filters sharing one would both run and stack their conditions.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
final class HasReferencesFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'hasReferences';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $state->getQuery()->where('discussions.references_count', $negate ? '=' : '>', 0);
    }
}
