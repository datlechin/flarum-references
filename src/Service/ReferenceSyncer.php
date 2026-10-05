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
use Datlechin\References\Extraction\ExtractedReference;
use Datlechin\References\Extraction\ExtractorInterface;
use Datlechin\References\Extraction\ParsedContent;
use Datlechin\References\Reference;
use Datlechin\References\RelationType;
use Datlechin\References\Settings\Config;
use Datlechin\References\Target\TargetRegistry;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

final class ReferenceSyncer
{
    /**
     * @param iterable<ExtractorInterface> $extractors
     */
    public function __construct(
        private iterable $extractors,
        private TargetRegistry $targets,
        private ReferenceCounter $counter,
        private Config $config,
    ) {
    }

    /**
     * @param bool $refreshCounters false when the caller recounts everything
     *                              itself afterwards, as the backfill does
     * @return list<Reference> rows created by this pass
     */
    public function sync(Post $post, bool $deleteOrphans = true, bool $refreshCounters = true): array
    {
        if (! $this->config->enabled() || ! $post instanceof CommentPost) {
            return [];
        }

        $extracted = $this->resolve($post, $this->extract($post));

        $created = [];

        foreach ($extracted as [$reference, $targetDiscussionId]) {
            $row = $this->create($post, $reference, $targetDiscussionId);

            if ($row !== null) {
                $created[] = $row;
            }
        }

        $touched = array_map(fn (Reference $row) => $row->target_discussion_id, $created);

        if ($deleteOrphans) {
            $touched = [...$touched, ...$this->delete($this->orphans($post, array_keys($extracted)))];
        }

        if ($refreshCounters) {
            $this->counter->refresh($touched);
        }

        return $created;
    }

    public function clear(Post $post): void
    {
        $this->counter->refresh($this->delete(Reference::query()->where('source_post_id', $post->id)));
    }

    /**
     * @return array<string, ExtractedReference>
     */
    private function extract(Post $post): array
    {
        $xml = $post->parsed_content;

        if (! is_string($xml) || $xml === '') {
            return [];
        }

        $content = new ParsedContent($xml);
        $extracted = [];

        foreach ($this->extractors as $extractor) {
            foreach ($extractor->extract($content) as $reference) {
                // First extractor to find a target owns the row's origin. A
                // post that both mentions and links the same target is one
                // assertion, not two.
                $extracted[$reference->key()] ??= $reference;
            }
        }

        return $extracted;
    }

    /**
     * Each target that exists and lives somewhere other than the post's own
     * discussion, with the discussion it does live in.
     *
     * A link to something that was never there has nothing to record, and
     * nothing to report as broken either: the report is about things that
     * went away. A link back into the same discussion is the discussion
     * talking to itself. A reply is a post mention of the post above it, and
     * recording those put every reply in the reference list.
     *
     * @param array<string, ExtractedReference> $extracted
     * @return array<string, array{ExtractedReference, int|null}>
     */
    private function resolve(Post $post, array $extracted): array
    {
        $idsByType = [];

        foreach ($extracted as $reference) {
            $idsByType[$reference->targetType][] = $reference->targetId;
        }

        $resolved = [];

        foreach ($idsByType as $type => $ids) {
            $target = $this->targets->get($type);

            if ($target === null) {
                continue;
            }

            /** @var class-string<Model> $modelClass */
            $modelClass = $target->modelClass();

            foreach ($modelClass::query()->whereIn('id', $ids)->get() as $model) {
                $key = $model->getKey();

                if (! is_int($key) && ! is_string($key)) {
                    continue;
                }

                $discussionId = $target->discussionIdFor($model);

                if ($discussionId !== null && $discussionId === (int) $post->discussion_id) {
                    continue;
                }

                $resolved[$type.':'.$key] = [$extracted[$type.':'.$key], $discussionId];
            }
        }

        return $resolved;
    }

    private function create(Post $post, ExtractedReference $reference, ?int $targetDiscussionId): ?Reference
    {
        $identity = [
            'source_post_id' => $post->id,
            'target_type' => $reference->targetType,
            'target_id' => $reference->targetId,
        ];

        if (Reference::query()->where($identity)->exists()) {
            return null;
        }

        $row = new Reference;
        $row->forceFill($identity + [
            'source_discussion_id' => $post->discussion_id,
            'target_discussion_id' => $targetDiscussionId,
            'relation_type' => RelationType::References,
            'origin' => $reference->origin,
            'created_at' => Carbon::now(),
        ]);

        try {
            $row->save();
        } catch (UniqueConstraintViolationException) {
            // Only the unique key losing a race. Anything else is a real
            // failure and is left to propagate.
            return null;
        }

        return $row;
    }

    /**
     * What this post no longer points at, and what it points at inside its own
     * discussion, which an earlier version of this extension recorded.
     *
     * @param list<string> $keep
     * @return Builder<Reference>
     */
    private function orphans(Post $post, array $keep): Builder
    {
        $query = Reference::query()
            ->where('source_post_id', $post->id)
            // A moderator's classification outlives the author editing the
            // text that first produced the row.
            ->where('relation_type', RelationType::References->value)
            ->whereNull('note');

        foreach ($keep as $key) {
            [$type, $id] = explode(':', $key, 2);

            $query->where(function (Builder $query) use ($type, $id) {
                $query->where('target_type', '!=', $type)->orWhere('target_id', '!=', (int) $id);
            });
        }

        return $query;
    }

    /**
     * @param Builder<Reference> $query
     * @return list<int|null> the discussions whose counter the delete moved
     */
    private function delete(Builder $query): array
    {
        $rows = $query->get(['id', 'target_discussion_id']);

        if ($rows->isEmpty()) {
            return [];
        }

        Reference::query()->whereIn('id', $rows->pluck('id')->all())->delete();

        return array_values($rows->map(fn (Reference $row) => $row->target_discussion_id)->all());
    }
}
