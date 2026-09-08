<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Contract;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One kind of thing a reference can point at.
 *
 * How a reference is found is a separate question, answered by the extractors
 * in {@see \Datlechin\References\Extraction}. Nothing here reads post content.
 */
interface ReferenceTarget
{
    /**
     * Written to `post_references.target_type` and reused as the Eloquent
     * morph alias and the JSON:API type. It cannot change once released.
     * Namespace it (`acme-wiki.page`) when it ships in a third-party
     * extension.
     */
    public function key(): string;

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string;

    /**
     * Scoped to what the actor may see. Callers narrow it by id themselves.
     *
     * Always answer this honestly: `countRelation` runs through
     * `EloquentBuffer::loadAggregate()`, which skips a resource's own
     * `scope()`, so a query that skips the check here leaks the existence of
     * targets the actor cannot open.
     *
     * @return Builder<covariant Model>
     */
    public function query(User $actor): Builder;

    /**
     * The id this internal path points at, or null when the path is not this
     * target's. Every registered target is asked in turn.
     */
    public function matchUrl(string $path): ?int;

    /**
     * The discussion the target lives in, or null when the target is not
     * discussion-shaped. Null forfeits the grouped list at the discussion, the
     * event post and follower notifications, and nothing else.
     */
    public function discussionIdFor(Model $target): ?int;

    public function title(Model $target): string;

    public function url(Model $target): string;

    public function author(Model $target): ?User;
}
