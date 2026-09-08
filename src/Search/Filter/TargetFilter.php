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
use Illuminate\Database\Eloquent\Builder;

/**
 * `filter[target]=discussions:12`, which the full backlink list pages through
 * once the capped preview runs out.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
final class TargetFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'target';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $entries = [];

        foreach ($this->asStringArray($value) as $entry) {
            if (is_string($entry) && str_contains($entry, ':')) {
                $entries[] = $entry;
            }
        }

        if ($entries === []) {
            return;
        }

        $matching = function (Builder $query) use ($entries) {
            foreach ($entries as $entry) {
                [$type, $id] = explode(':', $entry, 2);

                $query->orWhere(fn (Builder $query) => $query
                    ->where('post_references.target_type', $type)
                    ->where('post_references.target_id', (int) $id));
            }
        };

        $query = $state->getQuery();

        $negate ? $query->whereNot($matching) : $query->where($matching);
    }
}
