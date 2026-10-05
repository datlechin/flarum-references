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
use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;
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
        private TranslatorInterface $translator,
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

    /**
     * Refusals are validation errors on `targetId`, so the modal puts the
     * reason beside the field instead of failing with a bare 400.
     */
    public function prepare(Reference $reference, Context $context): void
    {
        if ($reference->source_discussion_id === null) {
            throw new ValidationException([], [
                'sourceDiscussion' => $this->translator->trans('datlechin-references.api.manual.source_required_message'),
            ]);
        }

        $target = $this->targets->get((string) $reference->target_type);
        $model = $target?->query($context->getActor())->find($reference->target_id);

        if ($target === null || ! $model instanceof Model) {
            $this->refuse('not_found');
        }

        $reference->target_discussion_id = $target->discussionIdFor($model);

        // A discussion pointing at itself, or at a post inside itself, says
        // nothing anybody can use, and every list would have to hide it.
        if ($reference->target_discussion_id !== null && $reference->target_discussion_id === $reference->source_discussion_id) {
            $this->refuse('same_discussion');
        }

        $duplicate = Reference::query()
            ->whereNull('source_post_id')
            ->where('source_discussion_id', $reference->source_discussion_id)
            ->where('target_type', $reference->target_type)
            ->where('target_id', $reference->target_id)
            ->exists();

        if ($duplicate) {
            $this->refuse('duplicate');
        }
    }

    private function refuse(string $reason): never
    {
        throw new ValidationException([
            'targetId' => $this->translator->trans('datlechin-references.api.manual.'.$reason.'_message'),
        ]);
    }
}
