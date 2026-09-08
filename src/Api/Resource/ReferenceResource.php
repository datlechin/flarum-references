<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Api\Resource;

use Carbon\Carbon;
use Datlechin\References\Reference;
use Datlechin\References\ReferenceOrigin;
use Datlechin\References\RelationType;
use Datlechin\References\Service\ManualReferenceCreator;
use Datlechin\References\Service\ReferenceCounter;
use Datlechin\References\Target\TargetRegistry;
use Flarum\Api\Context as FlarumContext;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Discussion\Discussion;
use Flarum\Http\Exception\InvalidParameterException;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Database\Eloquent\Builder;
use Tobyz\JsonApiServer\Context;

/**
 * @extends AbstractDatabaseResource<Reference>
 */
class ReferenceResource extends AbstractDatabaseResource
{
    public const PERMISSION = 'datlechin-references.manageReferences';

    public function __construct(
        protected TargetRegistry $targets,
        protected ManualReferenceCreator $creator,
        protected ReferenceCounter $counter,
    ) {
    }

    public function type(): string
    {
        return 'post-references';
    }

    public function model(): string
    {
        return Reference::class;
    }

    public function scope(Builder $query, Context $context): void
    {
        $query->whereVisibleTo($context->getActor());
    }

    public function newModel(Context $context): object
    {
        if ($context instanceof FlarumContext && $context->creating(self::class)) {
            return $this->creator->build($context);
        }

        return parent::newModel($context);
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->defaultInclude(['sourcePost', 'sourcePost.user', 'sourceDiscussion', 'target'])
                ->eagerLoad(['sourcePost.discussion'])
                ->defaultSort('-createdAt')
                ->paginate(),
            Endpoint\Show::make()
                ->defaultInclude(['sourcePost', 'sourceDiscussion', 'target']),
            Endpoint\Create::make()
                ->authenticated()
                ->can(self::PERMISSION)
                ->defaultInclude(['sourceDiscussion', 'target', 'createdBy']),
            Endpoint\Update::make()
                ->authenticated()
                ->can(self::PERMISSION),
            Endpoint\Delete::make()
                ->authenticated()
                ->can(self::PERMISSION),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('relationType')
                ->writable()
                ->get(fn (Reference $reference) => $reference->relation_type->value)
                ->set(function (Reference $reference, string $value) {
                    $relation = RelationType::tryFrom($value);

                    if ($relation === null) {
                        throw new InvalidParameterException;
                    }

                    $reference->relation_type = $relation;
                    $reference->updated_at = Carbon::now();
                }),
            Schema\Str::make('note')
                ->writable()
                ->nullable(),
            Schema\Str::make('origin')
                ->get(fn (Reference $reference) => $reference->origin->value),
            Schema\Str::make('targetType')
                ->writableOnCreate(),
            Schema\Integer::make('targetId')
                ->writableOnCreate(),
            Schema\Boolean::make('broken')
                ->get(fn (Reference $reference) => $reference->isBroken()),
            Schema\DateTime::make('createdAt'),

            Schema\Relationship\ToOne::make('sourcePost')
                ->type('posts')
                ->nullable()
                ->includable(),
            Schema\Relationship\ToOne::make('sourceDiscussion')
                ->type('discussions')
                ->includable()
                ->writableOnCreate()
                ->set(function (Reference $reference, Discussion $discussion, FlarumContext $context) {
                    $context->getActor()->assertCan('view', $discussion);

                    $reference->source_discussion_id = $discussion->id;
                }),
            Schema\Relationship\ToOne::make('targetDiscussion')
                ->type('discussions')
                ->nullable()
                ->includable(),
            Schema\Relationship\ToOne::make('createdBy')
                ->type('users')
                ->nullable()
                ->includable(),

            // A genuine morph, the same shape `NotificationResource::subject`
            // uses. Each target type's own resource `scope()` is applied by
            // EloquentBuffer, so a third-party target inherits visibility
            // without writing a query.
            Schema\Relationship\ToOne::make('target')
                ->collection($this->targets->keys())
                ->nullable()
                ->includable(),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('createdAt'),
        ];
    }

    public function saving(object $model, Context $context): ?object
    {
        if ($context instanceof FlarumContext && $context->creating(self::class)) {
            $this->creator->prepare($model, $context);
        }

        return parent::saving($model, $context);
    }

    /**
     * The counter moves here rather than while building the row, so a save
     * that never lands cannot leave it inflated.
     */
    public function created(object $model, Context $context): ?object
    {
        if (! $model->isBroken()) {
            $this->counter->increment($model->target_discussion_id);
        }

        return parent::created($model, $context);
    }

    public function deleting(object $model, Context $context): void
    {
        // An extracted row is retracted by editing the post that wrote it.
        // Deleting it here would only invite the next sync to write it again.
        if ($model->origin !== ReferenceOrigin::Manual) {
            throw new PermissionDeniedException;
        }

        if (! $model->isBroken() && $model->target_discussion_id !== null) {
            $this->counter->apply([$model->target_discussion_id => -1]);
        }
    }
}
