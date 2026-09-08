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
     * @return list<Reference> rows created by this pass
     */
    public function sync(Post $post, bool $deleteOrphans = true): array
    {
        if (! $this->config->enabled() || ! $post instanceof CommentPost) {
            return [];
        }

        $extracted = $this->extract($post);
        $hostDiscussions = $this->hostDiscussions($extracted);

        $created = [];
        $increments = [];

        foreach ($extracted as $key => $reference) {
            // A target that does not resolve is a link to something that was
            // never there. Nothing to record, and nothing to report as broken
            // either: the report is about things that went away.
            if (! array_key_exists($key, $hostDiscussions)) {
                continue;
            }

            $row = $this->create($post, $reference, $hostDiscussions[$key]);

            if ($row === null) {
                continue;
            }

            $created[] = $row;

            if ($row->target_discussion_id !== null) {
                $increments[$row->target_discussion_id] = ($increments[$row->target_discussion_id] ?? 0) + 1;
            }
        }

        $this->counter->apply($increments);

        if ($deleteOrphans) {
            $this->delete($this->orphans($post, array_keys($extracted)));
        }

        return $created;
    }

    public function clear(Post $post): void
    {
        $this->delete(Reference::query()->where('source_post_id', $post->id));
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

        $extracted = [];

        foreach ($this->extractors as $extractor) {
            foreach ($extractor->extract($xml) as $reference) {
                // First extractor to find a target owns the row's origin. A
                // post that both mentions and links the same target is one
                // assertion, not two.
                $extracted[$reference->key()] ??= $reference;
            }
        }

        return $extracted;
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
            // failure and is left to propagate. Matching on SQLSTATE by hand
            // read `23000`, which is what MySQL and SQLite report but not
            // Postgres, where the rethrow reached the caller and failed the
            // post. It was also wider than intended on MySQL, where `23000`
            // covers foreign key and NOT NULL failures too.
            return null;
        }

        return $row;
    }

    /**
     * @param array<string, ExtractedReference> $extracted
     * @return array<string, int|null> keyed as $extracted is
     */
    private function hostDiscussions(array $extracted): array
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

                if (is_int($key) || is_string($key)) {
                    $resolved[$type.':'.$key] = $target->discussionIdFor($model);
                }
            }
        }

        return $resolved;
    }

    /**
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
     */
    private function delete(Builder $query): void
    {
        $decrements = [];
        $ids = [];

        foreach ($query->get(['id', 'target_discussion_id', 'target_deleted_at']) as $row) {
            $ids[] = $row->id;

            // A broken row already left the counter when it was marked.
            if ($row->target_discussion_id !== null && ! $row->isBroken()) {
                $decrements[$row->target_discussion_id] = ($decrements[$row->target_discussion_id] ?? 0) - 1;
            }
        }

        if ($ids === []) {
            return;
        }

        Reference::query()->whereIn('id', $ids)->delete();

        $this->counter->apply($decrements);
    }
}
