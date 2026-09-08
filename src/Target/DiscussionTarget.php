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
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class DiscussionTarget implements ReferenceTarget
{
    public function __construct(
        private UrlGenerator $url,
        private SlugManager $slugs,
    ) {
    }

    public function key(): string
    {
        return Reference::TARGET_DISCUSSION;
    }

    public function modelClass(): string
    {
        return Discussion::class;
    }

    public function query(User $actor): Builder
    {
        return Discussion::whereVisibleTo($actor);
    }

    public function matchUrl(string $path): ?int
    {
        // No trailing segment, so `/d/12/34` belongs to the post target and
        // the two never race.
        return preg_match('~^/d/(\d+)(?:-[^/]*)?$~', $path, $matches) === 1
            ? (int) $matches[1]
            : null;
    }

    public function discussionIdFor(Model $target): ?int
    {
        return $target instanceof Discussion ? (int) $target->id : null;
    }

    public function title(Model $target): string
    {
        return $target instanceof Discussion ? $target->title : '';
    }

    public function url(Model $target): string
    {
        if (! $target instanceof Discussion) {
            return '';
        }

        return $this->url->to('forum')->route('discussion', [
            'id' => $this->slugs->forResource(Discussion::class)->toSlug($target),
        ]);
    }

    public function author(Model $target): ?User
    {
        return $target instanceof Discussion ? $target->user : null;
    }
}
