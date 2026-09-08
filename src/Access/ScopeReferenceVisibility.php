<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Access;

use Datlechin\References\Target\TargetRegistry;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * A reference is visible when both ends are.
 *
 * Written as uncorrelated subqueries rather than `whereHas`, for the reason
 * `ScopePostVisibility` gives for the same choice: many rows share few
 * discussions, so this pays the visibility cost once per query instead of once
 * per row.
 *
 * A target type nobody has registered is unjudgeable, so it stays hidden. That
 * covers references left behind by a disabled extension, and rows whose target
 * has been deleted.
 */
final class ScopeReferenceVisibility
{
    public function __construct(
        private TargetRegistry $targets,
    ) {
    }

    public function __invoke(User $actor, Builder $query): void
    {
        $query
            ->where(function (Builder $query) use ($actor) {
                $query
                    ->whereIn('post_references.source_post_id', Post::whereVisibleTo($actor)->select('posts.id'))
                    ->orWhere(function (Builder $query) use ($actor) {
                        $query
                            ->whereNull('post_references.source_post_id')
                            ->whereIn(
                                'post_references.source_discussion_id',
                                Discussion::whereVisibleTo($actor)->select('discussions.id'),
                            );
                    });
            })
            ->where(function (Builder $query) use ($actor) {
                $query->whereRaw('1 = 0');

                foreach ($this->targets->all() as $target) {
                    $query->orWhere(function (Builder $query) use ($target, $actor) {
                        $query
                            ->where('post_references.target_type', $target->key())
                            ->whereIn('post_references.target_id', $target->query($actor)->select('id'));
                    });
                }
            });
    }
}
