<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Target;

use Datlechin\References\Contract\ReferenceTarget;
use Datlechin\References\Reference;
use Flarum\Discussion\Discussion;
use Flarum\Http\SlugManager;
use Flarum\Http\UrlGenerator;
use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class PostTarget implements ReferenceTarget
{
    public function __construct(
        private UrlGenerator $url,
        private SlugManager $slugs,
    ) {
    }

    public function key(): string
    {
        return Reference::TARGET_POST;
    }

    public function modelClass(): string
    {
        return Post::class;
    }

    public function query(User $actor): Builder
    {
        return Post::whereVisibleTo($actor);
    }

    /**
     * An address names a post by its number within a discussion, so this
     * costs one query. It only ever runs while a post is being saved.
     */
    public function matchUrl(string $path): ?int
    {
        if (preg_match('~^/d/(\d+)(?:-[^/]*)?/(\d+)$~', $path, $matches) !== 1) {
            return null;
        }

        $id = Post::query()
            ->where('discussion_id', (int) $matches[1])
            ->where('number', (int) $matches[2])
            ->value('id');

        return is_int($id) ? $id : null;
    }

    public function discussionIdFor(Model $target): ?int
    {
        return $target instanceof Post ? (int) $target->discussion_id : null;
    }

    public function title(Model $target): string
    {
        return $target instanceof Post && $target->discussion instanceof Discussion
            ? $target->discussion->title
            : '';
    }

    public function url(Model $target): string
    {
        if (! $target instanceof Post || ! $target->discussion instanceof Discussion) {
            return '';
        }

        return $this->url->to('forum')->route('discussion', [
            'id' => $this->slugs->forResource(Discussion::class)->toSlug($target->discussion),
            'near' => $target->number,
        ]);
    }

    public function author(Model $target): ?User
    {
        return $target instanceof Post ? $target->user : null;
    }
}
