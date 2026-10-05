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

use Closure;
use Datlechin\References\Api\Resource\ReferenceResource;
use Datlechin\References\Service\DiscussionReferenceQuery;
use Datlechin\References\Settings\Config;
use Flarum\Api\Context;
use Flarum\Api\Resource\DiscussionResource;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;

/**
 * The sidebar of one discussion, and nothing on the list.
 *
 * Every field here is served by the discussion's own page only. Two
 * visibility scoped counts over the reference table on every discussion in
 * every list page cost the busiest endpoint on the forum for numbers nothing
 * on the list ever drew.
 */
final class DiscussionResourceFields
{
    public function __construct(
        private Config $config,
        private DiscussionReferenceQuery $references,
    ) {
    }

    public function __invoke(): array
    {
        return [
            // Distinct discussions, matching the list beneath the count.
            Schema\Integer::make('referencedByCount')
                ->visible(self::onShow())
                ->get(fn (Discussion $discussion, Context $context) => $this->references->incomingCount((int) $discussion->id, $context->getActor())),

            Schema\Integer::make('outgoingReferencesCount')
                ->visible(self::onShow())
                ->get(fn (Discussion $discussion, Context $context) => $this->references->outgoingCount((int) $discussion->id, $context->getActor())),

            Schema\Relationship\ToMany::make('referencedBy')
                ->type('post-references')
                ->includable()
                ->visible(self::onShow())
                ->get(fn (Discussion $discussion, Context $context) => $this->references
                    ->incoming((int) $discussion->id, $context->getActor())
                    ->with('sourcePost.user', 'sourceDiscussion.user')
                    ->orderByDesc('post_references.id')
                    ->limit($this->config->maxPreview())
                    ->get()
                    ->all()),

            Schema\Relationship\ToMany::make('outgoingReferences')
                ->type('post-references')
                ->includable()
                ->visible(self::onShow())
                ->get(fn (Discussion $discussion, Context $context) => $this->references
                    ->outgoing((int) $discussion->id, $context->getActor())
                    ->with('targetDiscussion')
                    ->orderByDesc('post_references.id')
                    ->limit($this->config->maxPreview())
                    ->get()
                    ->all()),

            Schema\Boolean::make('canManageReferences')
                ->get(fn (Discussion $discussion, Context $context) => $context->getActor()->hasPermission(ReferenceResource::PERMISSION)),
        ];
    }

    /**
     * Called with the model and the context while serialising, and with the
     * context alone while the request's includes are checked.
     */
    private static function onShow(): Closure
    {
        return function (mixed ...$arguments): bool {
            $context = end($arguments);

            return $context instanceof Context && $context->showing(DiscussionResource::class);
        };
    }
}
