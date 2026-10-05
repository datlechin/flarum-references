<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property int                $id
 * @property int                $source_discussion_id
 * @property int|null           $source_post_id
 * @property string             $target_type
 * @property int                $target_id
 * @property int|null           $target_discussion_id
 * @property RelationType       $relation_type
 * @property ReferenceOrigin    $origin
 * @property int|null           $created_by_id
 * @property string|null        $note
 * @property Carbon|null        $target_deleted_at
 * @property Carbon             $created_at
 * @property Carbon|null        $updated_at
 * @property-read Discussion    $sourceDiscussion
 * @property-read Post|null     $sourcePost
 * @property-read Discussion|null $targetDiscussion
 * @property-read User|null     $createdBy
 * @property-read Model|null    $target
 *
 * @method static Builder<self> counted()
 */
class Reference extends AbstractModel
{
    use ScopeVisibilityTrait;

    public const TARGET_DISCUSSION = 'discussions';
    public const TARGET_POST = 'posts';

    // Eloquent would guess `references` from the class name.
    protected $table = 'post_references';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'relation_type' => RelationType::class,
            'origin' => ReferenceOrigin::class,
            'target_deleted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function sourceDiscussion(): BelongsTo
    {
        return $this->belongsTo(Discussion::class, 'source_discussion_id');
    }

    public function sourcePost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'source_post_id');
    }

    public function targetDiscussion(): BelongsTo
    {
        return $this->belongsTo(Discussion::class, 'target_discussion_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function target(): MorphTo
    {
        return $this->morphTo('target', 'target_type', 'target_id');
    }

    public function isBroken(): bool
    {
        return $this->target_deleted_at !== null;
    }

    /**
     * A moderator has said something about this row, which is what protects it
     * from the next edit of the post that produced it.
     */
    public function isClassified(): bool
    {
        return $this->relation_type !== RelationType::References || $this->note !== null;
    }

    /**
     * The rows that shape what everybody sees: the ranking counter, related
     * discussions and the graph. Not broken, and written somewhere a guest
     * could in principle read, so a hidden post or a discussion awaiting
     * approval cannot push another discussion up the list.
     *
     * Tag permissions are not considered. These are forum wide numbers, the
     * same for every reader, and core's own comment count makes the same call.
     *
     * @param Builder<self> $query
     */
    public function scopeCounted(Builder $query): void
    {
        $query
            ->whereNull('post_references.target_deleted_at')
            ->whereExists(fn (QueryBuilder $query) => $query
                ->selectRaw('1')
                ->from('discussions')
                ->whereColumn('discussions.id', 'post_references.source_discussion_id')
                ->whereNull('discussions.hidden_at')
                ->where('discussions.is_private', false))
            ->where(fn (Builder $query) => $query
                ->whereNull('post_references.source_post_id')
                ->orWhereExists(fn (QueryBuilder $query) => $query
                    ->selectRaw('1')
                    ->from('posts')
                    ->whereColumn('posts.id', 'post_references.source_post_id')
                    ->whereNull('posts.hidden_at')
                    ->where('posts.is_private', false)));
    }
}
