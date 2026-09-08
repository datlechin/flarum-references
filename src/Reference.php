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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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

    protected $casts = [
        'relation_type' => RelationType::class,
        'origin' => ReferenceOrigin::class,
        'target_deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
}
