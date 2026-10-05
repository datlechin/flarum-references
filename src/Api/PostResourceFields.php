<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Api;

use Datlechin\References\Post\ReferencedEventPost;
use Datlechin\References\ReferenceOrigin;
use Datlechin\References\Settings\Config;
use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Extension\ExtensionManager;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PostResourceFields
{
    public function __construct(
        private Config $config,
        private ExtensionManager $extensions,
    ) {
    }

    public function __invoke(): array
    {
        return [
            Schema\Integer::make('referencedByCount')
                // Repeated on purpose. An aggregate goes through
                // EloquentBuffer::loadAggregate(), which skips the resource's
                // own scope(), so a count that leaves this out would report
                // references the actor cannot open.
                ->countRelation('referencedBy', function (Builder $query, Context $context) {
                    $this->exceptWhatMentionsLists($query->whereVisibleTo($context->getActor()));
                }),

            Schema\Relationship\ToMany::make('referencedBy')
                ->type('post-references')
                ->includable()
                ->scope(function (HasMany $query) {
                    $this->exceptWhatMentionsLists($query->getQuery());

                    // On the relation, not its builder: only the relation
                    // turns a limit into one per post while eager loading.
                    $query->with('sourcePost', 'sourceDiscussion')->latest('id')->limit($this->config->maxPreview());
                }),

            // The posts an event post announces, as far as the reader may
            // follow them. Kept off the stored content so that a citation the
            // reader cannot open reads as "referenced this discussion" with no
            // title, rather than naming a discussion they cannot see.
            Schema\Relationship\ToMany::make('referenceSources')
                ->type('posts')
                ->includable()
                ->visible(fn (mixed ...$arguments) => ! isset($arguments[1]) || $arguments[0] instanceof ReferencedEventPost)
                ->get(fn (Post $post, Context $context) => $post instanceof ReferencedEventPost
                    ? CommentPost::whereVisibleTo($context->getActor())
                        ->whereIn('id', $post->sourcePostIds())
                        ->with('discussion')
                        ->get()
                        ->all()
                    : []),
        ];
    }

    /**
     * A post mention is listed under the post by Mentions already, as a reply.
     * The same row in our list beside it said the same thing twice in two
     * different ways.
     *
     * @template T of Builder
     *
     * @param T $query
     * @return T
     */
    private function exceptWhatMentionsLists(Builder $query): Builder
    {
        if ($this->extensions->isEnabled('flarum-mentions')) {
            $query->where('post_references.origin', '!=', ReferenceOrigin::Mention->value);
        }

        return $query;
    }
}
