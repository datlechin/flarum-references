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
use Datlechin\References\Notification\FollowedDiscussionReferencedBlueprint;
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

/**
 * Two audiences, two notification types. Whoever wrote what was referenced
 * hears "your post" or "your discussion"; followers of that discussion hear
 * "a discussion you follow". Followers used to be sent the author's
 * notification, which told each of them it was their post.
 */
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
            ->with('sourcePost.user', 'targetDiscussion')
            ->get();

        foreach ($references as $reference) {
            $source = $reference->sourcePost;

            if (! $source instanceof Post) {
                continue;
            }

            $target = $this->target($reference);
            $author = $this->author($reference, $target);
            $authors = $author !== null ? [$author->id => $author] : [];

            // Mentions tells the author of a mentioned post, and nobody else,
            // so followers still hear about it from here. The author hears
            // about it once, from Mentions.
            $personal = $this->announcedByMentions($reference) ? null : $this->personalBlueprint($reference, $source, $target);

            if ($personal !== null) {
                $this->sync($personal, $source, $authors);
            }

            $discussion = $reference->targetDiscussion;

            if ($discussion instanceof Discussion) {
                $this->sync(
                    new FollowedDiscussionReferencedBlueprint($discussion, $source),
                    $source,
                    array_diff_key($this->followers($discussion), $authors),
                );
            }
        }
    }

    public function retractFor(Post $post): void
    {
        $this->retract(Reference::query()->where('source_post_id', $post->id)->with('sourcePost', 'targetDiscussion')->get());
    }

    public function retractForDiscussion(int $discussionId): void
    {
        $this->retract(Reference::query()->where('source_discussion_id', $discussionId)->with('sourcePost', 'targetDiscussion')->get());
    }

    /**
     * @param Collection<int, Reference> $references
     */
    protected function retract(Collection $references): void
    {
        foreach ($references as $reference) {
            $source = $reference->sourcePost;

            if (! $source instanceof Post) {
                continue;
            }

            $personal = $this->personalBlueprint($reference, $source, $this->target($reference));

            if ($personal !== null) {
                $this->notifications->delete($personal);
            }

            if ($reference->targetDiscussion instanceof Discussion) {
                $this->notifications->delete(new FollowedDiscussionReferencedBlueprint($reference->targetDiscussion, $source));
            }
        }
    }

    /**
     * Mentions announced this exact pair already, to the author of the post
     * mentioned. Telling them twice would be our doing, not theirs.
     */
    protected function announcedByMentions(Reference $reference): bool
    {
        return $reference->origin === ReferenceOrigin::Mention && $this->extensions->isEnabled('flarum-mentions');
    }

    protected function personalBlueprint(Reference $reference, Post $source, ?Model $target): ?BlueprintInterface
    {
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

    protected function author(Reference $reference, ?Model $target): ?User
    {
        $handler = $this->targets->get($reference->target_type);

        if ($target === null || $handler === null) {
            return null;
        }

        $author = $handler->author($target);

        return $author instanceof User && $author->id !== null ? $author : null;
    }

    /**
     * Nobody needs telling that they linked something themselves, and nobody
     * should hear about a post they cannot open.
     *
     * @param array<int, User> $recipients
     */
    protected function sync(BlueprintInterface $blueprint, Post $source, array $recipients): void
    {
        if ($source->user_id !== null) {
            unset($recipients[$source->user_id]);
        }

        $recipients = array_values(array_filter($recipients, fn (User $user) => $source->isVisibleTo($user)));

        if ($recipients !== []) {
            $this->notifications->sync($blueprint, $recipients);
        }
    }

    /**
     * @return array<int, User>
     */
    protected function followers(Discussion $discussion): array
    {
        if (! $this->config->notifyFollowers() || ! $this->extensions->isEnabled('flarum-subscriptions')) {
            return [];
        }

        $followers = [];

        $discussion->readers()
            ->where('discussion_user.subscription', 'follow')
            ->select('users.*')
            ->chunk(150, function (Collection $chunk) use (&$followers) {
                foreach ($chunk as $user) {
                    if ($user instanceof User && $user->id !== null) {
                        $followers[$user->id] = $user;
                    }
                }
            });

        return $followers;
    }
}
