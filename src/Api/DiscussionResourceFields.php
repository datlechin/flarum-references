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

use Datlechin\References\Api\Resource\ReferenceResource;
use Datlechin\References\Settings\Config;
use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DiscussionResourceFields
{
    public function __construct(
        private Config $config,
    ) {
    }

    public function __invoke(): array
    {
        return [
            // Both counts repeat the visibility constraint on purpose: an
            // aggregate runs through EloquentBuffer::loadAggregate(), which
            // skips the resource's own scope().
            Schema\Integer::make('referencedByCount')
                ->countRelation('referencedBy', function (Builder $query, Context $context) {
                    $query->whereVisibleTo($context->getActor());
                }),

            // Named after the relation, not shortened to `referencesCount`:
            // an aggregate is read back by the snake_case of the FIELD name,
            // and `references_count` is a real column on this table, so the
            // shorter name silently returns the ranking counter instead.
            Schema\Integer::make('outgoingReferencesCount')
                ->countRelation('outgoingReferences', function (Builder $query, Context $context) {
                    $query->whereVisibleTo($context->getActor());
                }),

            Schema\Relationship\ToMany::make('referencedBy')
                ->type('post-references')
                ->includable()
                ->scope(fn (HasMany $query) => $query
                    ->with('sourcePost.user', 'sourceDiscussion')
                    ->latest('id')
                    ->limit($this->config->maxPreview())),

            Schema\Relationship\ToMany::make('outgoingReferences')
                ->type('post-references')
                ->includable()
                ->scope(fn (HasMany $query) => $query
                    ->with('targetDiscussion')
                    ->latest('id')
                    ->limit($this->config->maxPreview())),

            Schema\Boolean::make('canManageReferences')
                ->get(fn (Discussion $discussion, Context $context) => $context->getActor()->hasPermission(ReferenceResource::PERMISSION)),
        ];
    }
}
