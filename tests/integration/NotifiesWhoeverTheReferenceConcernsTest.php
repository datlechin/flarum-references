<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Tests\integration;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The author of what was referenced is told it was theirs. A follower is told
 * it was a discussion they follow. Followers used to receive the author's
 * notification and read "referenced your post" about somebody else's.
 */
class NotifiesWhoeverTheReferenceConcernsTest extends TestCase
{
    use RecordsReferences;
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-subscriptions', 'flarum-mentions', 'datlechin-references');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'follower', 'password' => 'unused', 'email' => 'follower@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                $this->discussionRow(1, 'Seasoning a cast iron pan', userId: 2),
                $this->discussionRow(2, 'Rust, and what to do about it', userId: 1),
            ],
            Post::class => [
                [
                    'id' => 10,
                    'discussion_id' => 1,
                    'number' => 1,
                    'created_at' => '2026-01-01 00:00:00',
                    'user_id' => 2,
                    'type' => 'comment',
                    'content' => '<t>Start with a thin coat of oil.</t>',
                    'is_private' => false,
                ],
            ],
            'discussion_user' => [
                ['discussion_id' => 1, 'user_id' => 3, 'subscription' => 'follow'],
            ],
        ]);
    }

    /**
     * @return list<string>
     */
    protected function notificationsFor(int $userId): array
    {
        return $this->database()->table('notifications')
            ->where('user_id', $userId)
            ->where('is_deleted', false)
            ->orderBy('id')
            ->pluck('type')
            ->all();
    }

    #[Test]
    public function the_author_and_a_follower_are_each_told_in_their_own_words(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1', 1);

        $this->assertSame(['discussionReferenced'], $this->notificationsFor(2));
        $this->assertSame(['followedDiscussionReferenced'], $this->notificationsFor(3));
    }

    #[Test]
    public function a_reply_inside_the_discussion_is_left_to_mentions(): void
    {
        $this->reply(1, '@"normal"#p10 How thin is thin?', 1);

        $this->assertNotContains('discussionReferenced', $this->notificationsFor(2));
        $this->assertNotContains('postReferenced', $this->notificationsFor(2));
        $this->assertSame([], $this->notificationsFor(3));
    }

    #[Test]
    public function a_mention_from_another_discussion_reaches_followers_but_not_the_author_twice(): void
    {
        $this->reply(2, '@"normal"#p10 explains the oiling step.', 1);

        $this->assertSame(['postMentioned'], $this->notificationsFor(2));
        $this->assertSame(['followedDiscussionReferenced'], $this->notificationsFor(3));
    }

    #[Test]
    public function hiding_the_citing_post_takes_the_notifications_back(): void
    {
        $postId = (int) json_decode((string) $this->reply(2, 'See '.$this->forum().'/d/1', 1)->getBody(), true)['data']['id'];

        $this->send($this->request('PATCH', '/api/posts/'.$postId, [
            'authenticatedAs' => 1,
            'json' => ['data' => ['attributes' => ['isHidden' => true]]],
        ]));

        $this->assertSame([], $this->notificationsFor(2));
        $this->assertSame([], $this->notificationsFor(3));
    }
}
