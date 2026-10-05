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
use Datlechin\References\ReferenceOrigin;
use Flarum\Extension\ExtensionManager;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Illuminate\Database\Eloquent\Builder;

/**
 * `filter[target]=posts:12`, which the full list under a post pages through
 * once the capped preview runs out.
 *
 * A post target leaves out post mentions while Mentions is on, as the post's
 * own `referencedBy` does: Mentions lists those under the post already, and
 * the full list held rows its own count had left out.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
final class TargetFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function __construct(
        private ExtensionManager $extensions,
    ) {
    }

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

                $query->orWhere(function (Builder $query) use ($type, $id) {
                    $query
                        ->where('post_references.target_type', $type)
                        ->where('post_references.target_id', (int) $id);

                    if ($type === Reference::TARGET_POST && $this->extensions->isEnabled('flarum-mentions')) {
                        $query->where('post_references.origin', '!=', ReferenceOrigin::Mention->value);
                    }
                });
            }
        };

        $query = $state->getQuery();

        $negate ? $query->whereNot($matching) : $query->where($matching);
    }
}
