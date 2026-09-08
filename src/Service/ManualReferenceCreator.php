<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Service;

use Carbon\Carbon;
use Datlechin\References\Reference;
use Datlechin\References\ReferenceOrigin;
use Datlechin\References\RelationType;
use Datlechin\References\Target\TargetRegistry;
use Flarum\Api\Context;
use Flarum\Http\Exception\InvalidParameterException;
use Illuminate\Database\Eloquent\Model;

/**
 * A moderator's own claim about two things, made without editing anybody's
 * post. It carries no source post, which is also why the unique key cannot
 * catch a duplicate: null never equals null.
 */
final class ManualReferenceCreator
{
    public function __construct(
        private TargetRegistry $targets,
    ) {
    }

    public function build(Context $context): Reference
    {
        $reference = new Reference;

        $reference->origin = ReferenceOrigin::Manual;
        $reference->relation_type = RelationType::References;
        $reference->created_by_id = $context->getActor()->id;
        $reference->created_at = Carbon::now();

        return $reference;
    }

    public function prepare(Reference $reference, Context $context): void
    {
        $target = $this->targets->get($reference->target_type);

        if ($target === null) {
            throw new InvalidParameterException;
        }

        $model = $target->query($context->getActor())->find($reference->target_id);

        if (! $model instanceof Model) {
            throw new InvalidParameterException;
        }

        $reference->target_discussion_id = $target->discussionIdFor($model);

        $duplicate = Reference::query()
            ->whereNull('source_post_id')
            ->where('source_discussion_id', $reference->source_discussion_id)
            ->where('target_type', $reference->target_type)
            ->where('target_id', $reference->target_id)
            ->exists();

        if ($duplicate) {
            throw new InvalidParameterException;
        }
    }
}
