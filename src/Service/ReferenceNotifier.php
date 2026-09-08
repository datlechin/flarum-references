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

use Datlechin\References\Notification\DiscussionReferencedBlueprint;
use Datlechin\References\Notification\PostReferencedBlueprint;
use Datlechin\References\Reference;
use Datlechin\References\ReferenceOrigin;
use Datlechin\References\Settings\Config;
use Datlechin\References\Target\TargetRegistry;
use Flarum\Discussion\Discussion;
use Flarum\Extension\ExtensionManager;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

final class ReferenceNotifier
{
    public function __construct(
        private NotificationSyncer $notifications,
        private TargetRegistry $targets,
        private ExtensionManager $extensions,
        private Config $config,
    ) {
    }

    /**
     * @param list<int> $referenceIds
     */
    public function notify(array $referenceIds): void
    {
        $references = Reference::query()
            ->whereIn('id', $referenceIds)
            ->with('sourcePost.user')
            ->get();

        foreach ($references as $reference) {
            $blueprint = $this->blueprintFor($reference);

            if ($blueprint === null) {
                continue;
            }

            $recipients = $this->recipients($reference);

            if ($recipients !== []) {
                $this->notifications->sync($blueprint, $recipients);
            }
        }
    }

    public function retractFor(Post $post): void
    {
        $this->retract(Reference::query()->where('source_post_id', $post->id)->get());
    }

    public function retractForDiscussion(int $discussionId): void
    {
        $this->retract(Reference::query()->where('source_discussion_id', $discussionId)->get());
    }

    /**
     * @param Collection<int, Reference> $references
     */
    protected function retract(Collection $references): void
    {
        foreach ($references as $reference) {
            $blueprint = $this->blueprintFor($reference);

            if ($blueprint !== null) {
                $this->notifications->delete($blueprint);
            }
        }
    }

    protected function blueprintFor(Reference $reference): ?BlueprintInterface
    {
        $source = $reference->sourcePost;

        if (! $source instanceof Post) {
            return null;
        }

        // flarum/mentions already announced this exact pair. Saying it twice
        // is our doing, not theirs.
        if ($reference->origin === ReferenceOrigin::Mention && $this->extensions->isEnabled('flarum-mentions')) {
            return null;
        }

        $target = $this->target($reference);

        return match (true) {
            $target instanceof Discussion => new DiscussionReferencedBlueprint($target, $source),
            $target instanceof Post => new PostReferencedBlueprint($target, $source),
            default => null,
        };
    }

    protected function target(Reference $reference): ?Model
    {
        $target = $this->targets->get($reference->target_type);

        if ($target === null) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $target->modelClass();

        return $modelClass::query()->find($reference->target_id);
    }

    /**
     * @return list<User>
     */
    protected function recipients(Reference $reference): array
    {
        $source = $reference->sourcePost;
        $target = $this->target($reference);
        $handler = $this->targets->get($reference->target_type);

        if (! $source instanceof Post || $target === null || $handler === null) {
            return [];
        }

        $recipients = [];

        $author = $handler->author($target);

        if ($author instanceof User && $author->id !== null) {
            $recipients[$author->id] = $author;
        }

        foreach ($this->followers($reference) as $follower) {
            if ($follower->id !== null) {
                $recipients[$follower->id] ??= $follower;
            }
        }

        // Nobody needs telling that they linked something themselves, and
        // nobody should hear about a post they cannot open.
        if ($source->user_id !== null) {
            unset($recipients[$source->user_id]);
        }

        return array_values(array_filter(
            $recipients,
            fn (User $user) => $source->isVisibleTo($user),
        ));
    }

    /**
     * @return list<User>
     */
    protected function followers(Reference $reference): array
    {
        if (
            $reference->target_discussion_id === null
            || ! $this->config->notifyFollowers()
            || ! $this->extensions->isEnabled('flarum-subscriptions')
        ) {
            return [];
        }

        $discussion = Discussion::query()->find($reference->target_discussion_id);

        if (! $discussion instanceof Discussion) {
            return [];
        }

        $followers = [];

        $discussion->readers()
            ->where('discussion_user.subscription', 'follow')
            ->select('users.*')
            ->chunk(150, function (Collection $chunk) use (&$followers) {
                foreach ($chunk as $user) {
                    if ($user instanceof User) {
                        $followers[] = $user;
                    }
                }
            });

        return $followers;
    }
}
