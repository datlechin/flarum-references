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

use Datlechin\References\Settings\Config;
use Flarum\Api\Context;
use Flarum\Api\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PostResourceFields
{
    public function __construct(
        private Config $config,
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
                    $query->whereVisibleTo($context->getActor());
                }),

            Schema\Relationship\ToMany::make('referencedBy')
                ->type('post-references')
                ->includable()
                ->scope(fn (HasMany $query) => $query
                    ->with('sourcePost.user', 'sourceDiscussion')
                    ->latest('id')
                    ->limit($this->config->maxPreview())),
        ];
    }
}
